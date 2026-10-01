<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An in-clinic payment recorded by the cashier.
 * method: cash, gcash, maya, card (recorded manually - no online payment).
 */
class Payment extends Model
{
    public const METHODS = ['cash', 'gcash', 'maya', 'card'];

    protected $fillable = ['amount', 'method', 'reference_number', 'amount_tendered', 'change_given', 'paid_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_tendered' => 'decimal:2',
            'change_given' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
