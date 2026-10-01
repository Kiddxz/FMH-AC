<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One delivery of an item, with its own quantity and expiration date.
 */
class InventoryBatch extends Model
{
    protected $fillable = ['inventory_item_id', 'supplier_id', 'batch_number', 'expiration_date', 'received_date', 'unit_cost'];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
            'received_date' => 'date',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isExpired(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->isPast();
    }

    // Expiring within the given number of days (expiration alert, REQ016)
    public function isExpiringWithin(int $days): bool
    {
        return $this->expiration_date !== null
            && ! $this->isExpired()
            && $this->expiration_date->lte(now()->addDays($days));
    }
}
