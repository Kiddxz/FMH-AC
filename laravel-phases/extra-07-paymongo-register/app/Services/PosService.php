<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The cashier's work (capstone FR-REQ018 - FR-REQ020, Fig 6.5):
 *  - createBill()  saves the bill, its lines, the stock deduction and the first payment
 *  - addPayment()  collects (part of) the remaining balance
 *  - void()        cancels a bill with a reason and puts the sold products back in stock
 *
 * Each one runs inside ONE database transaction: if anything fails (e.g. not enough stock),
 * nothing is saved at all (NFR-REQ008). Prices and totals always come from the database,
 * never from the form. Money is counted in centavos so there are no rounding mistakes.
 * Payments are recorded by the cashier (cash, GCash, Maya, card), or online through the
 * bill's PayMongo pay link (method "paymongo", saved only after PayMongo confirms it, no cashier).
 */
class PosService
{
    public function __construct(private InventoryStock $stock)
    {
    }

    /**
     * $bill:  customer_id, pet_id, appointment_id, patient_visit_id, transaction_type, discount
     * $lines: [ ['type' => 'service'|'product', 'id' => 3, 'quantity' => 1], ... ]
     * $payment: null, or amount / method / reference_number / amount_tendered
     */
    public function createBill(array $bill, array $lines, ?array $payment, int $userId): Transaction
    {
        return DB::transaction(function () use ($bill, $lines, $payment, $userId) {
            $rows = [];
            $subtotal = 0;

            foreach ($lines as $line) {
                $quantity = (int) $line['quantity'];
                if ($line['type'] === 'service') {
                    $service = Service::where('is_active', true)->find($line['id']);
                    if (! $service) {
                        throw ValidationException::withMessages(['items' => 'One of the services is no longer offered.']);
                    }
                    $row = ['item_type' => 'service', 'service_id' => $service->id, 'description' => $service->name, 'price' => $this->cents($service->price)];
                } else {
                    $product = InventoryItem::where('is_active', true)->whereNotNull('selling_price')->find($line['id']);
                    if (! $product) {
                        throw ValidationException::withMessages(['items' => 'One of the products is not for sale.']);
                    }
                    $row = ['item_type' => 'product', 'inventory_item_id' => $product->id, 'description' => $product->name, 'price' => $this->cents($product->selling_price), 'product' => $product];
                }
                $row['quantity'] = $quantity;
                $row['line'] = $row['price'] * $quantity;
                $subtotal += $row['line'];
                $rows[] = $row;
            }

            $discount = $this->cents($bill['discount'] ?? 0);
            if ($discount > $subtotal) {
                throw ValidationException::withMessages(['discount' => 'The discount cannot be bigger than the subtotal.']);
            }

            $transaction = new Transaction($bill);
            $transaction->receipt_number = 'TMP-' . uniqid();   // replaced below, once the id is known
            $transaction->subtotal = $this->pesos($subtotal);
            $transaction->total = $this->pesos($subtotal - $discount);
            $transaction->balance = $transaction->total;
            $transaction->cashier_id = $userId;
            $transaction->status = $subtotal - $discount === 0 ? 'paid' : 'unpaid';   // fully discounted = nothing to pay
            $transaction->save();
            $transaction->receipt_number = sprintf('OR-%06d', $transaction->id);
            $transaction->save();

            foreach ($rows as $row) {
                $transaction->items()->create([
                    'item_type' => $row['item_type'],
                    'service_id' => $row['service_id'] ?? null,
                    'inventory_item_id' => $row['inventory_item_id'] ?? null,
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $this->pesos($row['price']),
                    'line_total' => $this->pesos($row['line']),
                ]);

                // Products leave the stock now (first-expiring batch first). Not enough stock = nothing is saved.
                if ($row['item_type'] === 'product') {
                    $this->stock->deduct($row['product'], $row['quantity'], 'sale', 'Sold on ' . $transaction->receipt_number, $userId, $transaction);
                }
            }

            if ($payment && $this->cents($payment['amount'] ?? 0) > 0) {
                $this->addPayment($transaction, $payment, $userId);
            }

            return $transaction->fresh();
        });
    }

    public function addPayment(Transaction $transaction, array $data, ?int $userId): Payment
    {
        return DB::transaction(function () use ($transaction, $data, $userId) {
            // lockForUpdate: two cashiers cannot collect the same balance at the same time
            $transaction = Transaction::lockForUpdate()->findOrFail($transaction->id);
            if (! in_array($transaction->status, ['unpaid', 'partial'], true)) {
                throw ValidationException::withMessages(['amount' => 'This bill is already ' . $transaction->status . '.']);
            }

            $amount = $this->cents($data['amount']);
            $balance = $this->cents($transaction->balance);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Enter the amount paid.']);
            }
            if ($amount > $balance) {
                throw ValidationException::withMessages(['amount' => 'The amount is more than the balance of ₱' . number_format($balance / 100, 2) . '.']);
            }

            // Cash: the customer may hand over more, and we give change. Other methods: exact amount only.
            $tendered = $amount;
            if ($data['method'] === 'cash' && isset($data['amount_tendered']) && $data['amount_tendered'] !== '') {
                $tendered = $this->cents($data['amount_tendered']);
                if ($tendered < $amount) {
                    throw ValidationException::withMessages(['amount_tendered' => 'The cash received is less than the amount to pay.']);
                }
            }

            $payment = new Payment([
                'amount' => $this->pesos($amount),
                'method' => $data['method'],
                'reference_number' => $data['method'] === 'cash' ? null : ($data['reference_number'] ?? null),
                'amount_tendered' => $this->pesos($tendered),
                'change_given' => $this->pesos($tendered - $amount),
                'paid_at' => now(),
            ]);
            $payment->transaction_id = $transaction->id;
            $payment->received_by = $userId;
            $payment->save();

            $paid = $this->cents($transaction->amount_paid) + $amount;
            $transaction->amount_paid = $this->pesos($paid);
            $transaction->balance = $this->pesos($balance - $amount);
            $transaction->status = $paid >= $this->cents($transaction->total) ? 'paid' : 'partial';
            $transaction->save();

            return $payment;
        });
    }

    public function void(Transaction $transaction, string $reason, int $userId): void
    {
        DB::transaction(function () use ($transaction, $reason, $userId) {
            $transaction = Transaction::lockForUpdate()->findOrFail($transaction->id);
            if ($transaction->status === 'void') {
                throw ValidationException::withMessages(['void_reason' => 'This bill is already void.']);
            }

            $transaction->status = 'void';
            $transaction->void_reason = $reason;
            $transaction->voided_by = $userId;
            $transaction->voided_at = now();
            $transaction->save();

            $this->stock->returnStock($transaction, 'Returned: ' . $transaction->receipt_number . ' was voided', $userId);
        });
    }

    private function cents(mixed $pesos): int
    {
        return (int) round(((float) $pesos) * 100);
    }

    private function pesos(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
