<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment on a bill.
 * method: cash, gcash, maya, card = recorded by the cashier in the clinic
 *         paymongo                 = paid online on the bill's pay link, confirmed by PayMongo
 */
class Payment extends Model
{
    // What the cashier can choose by hand. "paymongo" is never typed: only PayMongo's answer saves it.
    public const METHODS = ['cash', 'gcash', 'maya', 'card'];

    public const ONLINE = 'paymongo';

    // Names shown on pages, receipts and reports
    public const LABELS = ['cash' => 'Cash', 'gcash' => 'GCash', 'maya' => 'Maya', 'card' => 'Card', 'paymongo' => 'PayMongo (online)'];

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
