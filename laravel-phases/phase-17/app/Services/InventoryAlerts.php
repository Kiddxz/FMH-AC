<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\Setting;

/**
 * Low-stock and expiration alerts (capstone FR-REQ016), shown on the bell, the dashboards
 * and the inventory page. Nothing is sent outside the system.
 */
class InventoryAlerts
{
    // "Expiring soon" = within this many days. The Super Admin sets it in Settings (Phase 17); default 30.
    public static function expiringDays(): int
    {
        return max(1, (int) Setting::get('expiry_alert_days'));
    }

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
            ->whereDate('expiration_date', '<=', today()->addDays(self::expiringDays()))
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
