<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A medicine, vaccine, clinic supply or product (e.g. dog food).
 * The stock on hand is the total quantity of all its batches.
 */
class InventoryItem extends Model
{
    public const CATEGORIES = ['medicine', 'vaccine', 'supply', 'product'];

    protected $fillable = ['sku', 'name', 'category', 'unit', 'reorder_level', 'selling_price', 'supplier_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Total quantity on hand (all batches)
    public function getStockAttribute(): int
    {
        return (int) $this->batches()->sum('quantity');
    }

    // "Out of Stock", "Low Stock" or "Available" (low-stock alert, REQ016)
    public function getStockStatusAttribute(): string
    {
        $stock = $this->stock;

        if ($stock <= 0) {
            return 'Out of Stock';
        }

        return $stock <= $this->reorder_level ? 'Low Stock' : 'Available';
    }
}
