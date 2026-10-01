<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Inventory USAGE LOG row: every stock change (stock in, usage, sale, adjustment, expired).
 * Rows are only added, never edited.
 */
class InventoryMovement extends Model
{
    public const UPDATED_AT = null;   // the table has created_at only

    protected $fillable = [
        'inventory_item_id',
        'inventory_batch_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'remarks',
        'user_id',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // The visit or transaction that caused this movement (if any)
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
