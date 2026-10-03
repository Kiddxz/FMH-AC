<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every stock change goes through here, so the batches and the usage log always agree
 * (capstone FR-REQ014 - FR-REQ017).
 *
 *  - stockIn()     a delivery: a new batch with its own expiry date
 *  - deduct()      usage (or a sale in Phase 14): taken from the batch that expires first (FEFO)
 *  - correctCount() a physical count found a different quantity in one batch
 *  - disposeExpired() an expired batch is thrown away
 *
 * Expired batches are never used: they can only be disposed.
 */
class InventoryStock
{
    public function stockIn(InventoryItem $item, array $data, int $userId): InventoryBatch
    {
        return DB::transaction(function () use ($item, $data, $userId) {
            $batch = new InventoryBatch([
                'inventory_item_id' => $item->id,
                'supplier_id' => $data['supplier_id'] ?? $item->supplier_id,
                'batch_number' => $data['batch_number'] ?? null,
                'expiration_date' => $data['expiration_date'] ?? null,
                'received_date' => $data['received_date'] ?? today(),
                'unit_cost' => $data['unit_cost'] ?? null,
            ]);
            $batch->quantity = (int) $data['quantity'];
            $batch->save();

            $this->log($item, $batch, 'stock_in', $batch->quantity, $data['remarks'] ?? 'Stock received', $userId);

            return $batch;
        });
    }

    /**
     * Take stock out, first from the batch that expires first.
     * $type: 'usage' (clinic use) or 'sale' (POS, Phase 14).
     */
    public function deduct(InventoryItem $item, int $quantity, string $type, ?string $remarks, int $userId, ?Model $reference = null): void
    {
        DB::transaction(function () use ($item, $quantity, $type, $remarks, $userId, $reference) {
            // lockForUpdate: two people using the same item at the same time cannot both take the last pieces
            $batches = InventoryBatch::where('inventory_item_id', $item->id)
                ->where('quantity', '>', 0)
                ->where(fn ($q) => $q->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()))
                ->orderByRaw('expiration_date IS NULL')   // batches with an expiry date first
                ->orderBy('expiration_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $available = (int) $batches->sum('quantity');
            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$available} {$item->unit} of {$item->name} can be used (expired stock is not counted).",
                ]);
            }

            $left = $quantity;
            foreach ($batches as $batch) {
                if ($left === 0) {
                    break;
                }
                $take = min($left, $batch->quantity);
                $batch->quantity -= $take;
                $batch->save();
                $this->log($item, $batch, $type, -$take, $remarks, $userId, $reference);
                $left -= $take;
            }
        });
    }

    // A physical count: set the batch to the counted quantity and log the difference
    public function correctCount(InventoryBatch $batch, int $counted, string $reason, int $userId): int
    {
        return DB::transaction(function () use ($batch, $counted, $reason, $userId) {
            $batch = InventoryBatch::lockForUpdate()->findOrFail($batch->id);
            $difference = $counted - $batch->quantity;

            if ($difference !== 0) {
                $batch->quantity = $counted;
                $batch->save();
                $this->log($batch->item, $batch, 'adjustment', $difference, $reason, $userId);
            }

            return $difference;
        });
    }

    public function disposeExpired(InventoryBatch $batch, int $userId): int
    {
        if (! $batch->isExpired()) {
            throw ValidationException::withMessages(['batch' => 'Only expired batches can be disposed.']);
        }

        return DB::transaction(function () use ($batch, $userId) {
            $batch = InventoryBatch::lockForUpdate()->findOrFail($batch->id);
            $quantity = $batch->quantity;

            if ($quantity > 0) {
                $batch->quantity = 0;
                $batch->save();
                $this->log($batch->item, $batch, 'expired', -$quantity, 'Expired on ' . $batch->expiration_date->format('M j, Y') . ' - disposed', $userId);
            }

            return $quantity;
        });
    }

    private function log(InventoryItem $item, InventoryBatch $batch, string $type, int $quantity, ?string $remarks, int $userId, ?Model $reference = null): void
    {
        $movement = new InventoryMovement([
            'inventory_item_id' => $item->id,
            'inventory_batch_id' => $batch->id,
            'type' => $type,
            'quantity' => $quantity,
            'remarks' => $remarks,
            'user_id' => $userId,
        ]);
        if ($reference) {
            $movement->reference()->associate($reference);
        }
        $movement->save();
    }
}
