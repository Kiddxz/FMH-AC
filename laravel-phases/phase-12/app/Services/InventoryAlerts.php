<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;

/**
 * Low-stock and expiration alerts (capstone FR-REQ016), shown on the bell, the dashboards
 * and the inventory page. Nothing is sent outside the system.
 */
class InventoryAlerts
{
    public const EXPIRING_DAYS = 30;   // "expiring soon" = within 30 days

    public static function summary(): array
    {
        // Computed once per page (the bell and the dashboard both ask for it)
        $attributes = request()->attributes;
        if ($attributes->has('inventory_alerts')) {
            return $attributes->get('inventory_alerts');
        }

        // Low or out of stock (active items only); stock counts only batches that are not expired
        $low = InventoryItem::where('is_active', true)
            ->withSum(['batches as usable_stock' => fn ($q) => $q->where(fn ($b) => $b
                ->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()))], 'quantity')
            ->orderBy('name')
            ->get()
            ->filter(fn ($item) => (int) $item->usable_stock <= $item->reorder_level)
            ->values();

        $expiring = InventoryBatch::with('item')
            ->where('quantity', '>', 0)
            ->whereDate('expiration_date', '>=', today())
            ->whereDate('expiration_date', '<=', today()->addDays(self::EXPIRING_DAYS))
            ->orderBy('expiration_date')
            ->get();

        $expired = InventoryBatch::with('item')
            ->where('quantity', '>', 0)
            ->whereDate('expiration_date', '<', today())
            ->orderBy('expiration_date')
            ->get();

        $summary = [
            'low' => $low,
            'expiring' => $expiring,
            'expired' => $expired,
            'count' => $low->count() + $expiring->count() + $expired->count(),
        ];
        $attributes->set('inventory_alerts', $summary);

        return $summary;
    }
}
