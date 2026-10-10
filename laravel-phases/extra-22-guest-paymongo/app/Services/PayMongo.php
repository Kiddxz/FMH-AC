<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Talks to PayMongo (https://developers.paymongo.com) using its "Checkout" page:
 *  1. checkout() makes a PayMongo page for the bill's balance and gives its address
 *  2. the customer pays there with GCash, Maya or card
 *  3. paidPayments() asks PayMongo which payments of that page were successful
 * Only the secret key in .env is needed. No card or GCash details ever pass through our system.
 */
class PayMongo
{
    public static function enabled(): bool
    {
        return filled(config('paymongo.secret_key'));
    }

    /**
     * Makes a PayMongo checkout page for the balance of the bill.
     * Returns ['id' => 'cs_...', 'url' => 'https://checkout.paymongo.com/...']
     */
    public function checkout(Transaction $transaction, string $successUrl, string $cancelUrl): array
    {
        $balance = (int) round(((float) $transaction->balance) * 100);
        $clinic = Setting::get('clinic_name') ?: 'FMH Animal Clinic';

        // With a discount or a part already paid, one line with the balance is sent
        // (PayMongo cannot show a negative line). Otherwise the services/products are listed.
        $fullBill = (float) $transaction->discount == 0.0 && (float) $transaction->amount_paid == 0.0;
        $lines = $fullBill
            ? $transaction->items->map(fn ($item) => [
                'currency' => 'PHP',
                'amount' => (int) round(((float) $item->unit_price) * 100),
                'name' => mb_substr($item->description, 0, 255),
                'quantity' => (int) $item->quantity,
            ])->values()->all()
            : [[
                'currency' => 'PHP',
                'amount' => $balance,
                'name' => 'Balance of bill ' . $transaction->receipt_number,
                'quantity' => 1,
            ]];

        $response = $this->send('post', '/checkout_sessions', ['data' => ['attributes' => [
            'line_items' => $lines,
            'payment_method_types' => array_values(config('paymongo.methods')),
            'description' => $clinic . ' - Bill ' . $transaction->receipt_number,
            'reference_number' => $transaction->receipt_number,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'show_description' => true,
            'show_line_items' => true,
            'send_email_receipt' => false,
            'metadata' => ['transaction_id' => (string) $transaction->id],
        ]]]);

        return [
            'id' => $response['data']['id'],
            'url' => $response['data']['attributes']['checkout_url'],
        ];
    }

    /**
     * The successful payments of a checkout page: [['id' => 'pay_...', 'amount' => 150.00], ...]
     */
    public function paidPayments(string $checkoutId): array
    {
        $response = $this->send('get', '/checkout_sessions/' . rawurlencode($checkoutId));

        return collect($response['data']['attributes']['payments'] ?? [])
            ->filter(fn ($payment) => ($payment['attributes']['status'] ?? null) === 'paid')
            ->map(fn ($payment) => [
                'id' => $payment['id'],
                'amount' => ((int) $payment['attributes']['amount']) / 100,
            ])
            ->values()
            ->all();
    }

    private function send(string $method, string $path, array $body = []): array
    {
        try {
            $response = Http::withBasicAuth((string) config('paymongo.secret_key'), '')
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                ->{$method}(config('paymongo.api_url') . $path, $method === 'get' ? null : $body);
        } catch (ConnectionException $e) {
            Log::warning('PayMongo could not be reached: ' . $e->getMessage());
            throw ValidationException::withMessages(['paymongo' => 'Online payment is not available right now. Please check the internet connection and try again, or pay at the cashier.']);
        }

        if ($response->failed()) {
            // The details go to storage/logs/laravel.log. The customer only sees a simple message.
            Log::warning('PayMongo error ' . $response->status() . ': ' . $response->body());
            $reason = $response->json('errors.0.detail');
            throw ValidationException::withMessages(['paymongo' => 'PayMongo did not accept the request' . ($reason ? ': ' . $reason : '.') . ' Please pay at the cashier.']);
        }

        return $response->json();
    }
}
