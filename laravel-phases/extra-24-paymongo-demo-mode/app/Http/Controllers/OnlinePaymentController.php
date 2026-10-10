<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\PayMongo;
use App\Services\PosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Online payment of a bill with PayMongo (GCash, Maya, card).
 *
 * Walk-in customers do not need an account: the cashier saves the bill in the POS, then makes a
 * "pay link" for it. The link (or its QR code) opens a page that already shows the services, the
 * total and the balance, with a "Pay Online" button that goes to PayMongo.
 *
 *  Staff:    POST /staff/transactions/{id}/pay-link    make (or show) the pay link
 *            POST /staff/transactions/{id}/pay-check   ask PayMongo if the customer already paid
 *  Customer: GET  /pay/{token}                         the bill page (no login)
 *            POST /pay/{token}                         go to PayMongo to pay the balance
 *            GET  /pay/{token}/done                    back from PayMongo: the payment is checked and saved
 *  Demo mode (no PayMongo account yet):
 *            GET  /pay/{token}/demo                    the practice payment page (instead of PayMongo)
 *            POST /pay/{token}/demo                    "Pay" on the practice page (no real money)
 */
class OnlinePaymentController extends Controller
{
    // Staff: give the bill its pay link (the same link is kept once made)
    public function createLink(Request $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('pay', $transaction);
        $this->ensureEnabled();

        if (! $transaction->pay_token) {
            $transaction->pay_token = Str::random(40);
            $transaction->save();
            ActivityLog::record('created', 'POS', 'Made a PayMongo pay link for ' . $transaction->receipt_number . '.', $transaction);
        }

        return back()->with('status', 'Pay link ready. Let the customer scan the QR code or open the link.');
    }

    // Staff: "Check Online Payment" (when the customer paid but did not come back to our page)
    public function check(Request $request, Transaction $transaction, PayMongo $payMongo, PosService $pos): RedirectResponse
    {
        Gate::authorize('view', $transaction);
        $this->ensureEnabled();

        if (! $transaction->paymongo_checkout_id) {
            return back()->with('status', 'The customer has not opened the PayMongo payment page yet.');
        }

        $saved = $this->saveConfirmedPayments($transaction, $payMongo, $pos);
        $transaction->refresh();

        return back()->with('status', $saved > 0
            ? 'PayMongo payment of ₱' . number_format($saved, 2) . ' saved. ' . ($transaction->balance > 0 ? 'Balance: ₱' . number_format($transaction->balance, 2) . '.' : 'Fully paid.')
            : 'No new online payment yet.');
    }

    // Customer: the bill page
    public function show(string $token): View
    {
        $transaction = $this->findByToken($token);

        return view('pay.show', [
            'transaction' => $transaction,
            'enabled' => PayMongo::enabled(),
            'minimum' => (float) config('paymongo.minimum'),
            'demo' => PayMongo::demo(),
        ]);
    }

    // Demo mode: the practice page that takes the place of the PayMongo page
    public function demoPage(string $token): View|RedirectResponse
    {
        $transaction = $this->findDemoBill($token);
        if (! in_array($transaction->status, ['unpaid', 'partial'], true)) {
            return redirect()->route('pay.show', $token);
        }

        return view('pay.demo', ['transaction' => $transaction]);
    }

    // Demo mode: "Pay" on the practice page. Then back to /done, the same as after PayMongo.
    public function demoPay(Request $request, string $token): RedirectResponse
    {
        $transaction = $this->findDemoBill($token);
        $request->validate(['method' => ['required', 'in:gcash,paymaya,card']]);

        if (in_array($transaction->status, ['unpaid', 'partial'], true) && $transaction->balance > 0) {
            PayMongo::recordDemoPayment($transaction->paymongo_checkout_id, (float) $transaction->balance);
        }

        return redirect()->route('pay.done', $token);
    }

    // Customer: "Pay Online" -> a new PayMongo page for the current balance
    public function checkout(string $token, PayMongo $payMongo): RedirectResponse
    {
        $transaction = $this->findByToken($token);
        $this->ensureEnabled();

        if (! in_array($transaction->status, ['unpaid', 'partial'], true) || $transaction->balance <= 0) {
            return redirect()->route('pay.show', $token);
        }
        if ((float) $transaction->balance < (float) config('paymongo.minimum')) {
            throw ValidationException::withMessages(['paymongo' => 'Online payment needs at least ₱' . number_format((float) config('paymongo.minimum'), 2) . '. Please pay this balance at the cashier.']);
        }

        $page = $payMongo->checkout(
            $transaction,
            route('pay.done', $token),
            route('pay.show', $token),
        );

        $transaction->paymongo_checkout_id = $page['id'];
        $transaction->save();

        return redirect()->away($page['url']);
    }

    // Customer: back from PayMongo. PayMongo is asked directly; the address alone never marks a bill paid.
    public function done(string $token, PayMongo $payMongo, PosService $pos): RedirectResponse
    {
        $transaction = $this->findByToken($token);

        if (PayMongo::enabled() && $transaction->paymongo_checkout_id) {
            $saved = $this->saveConfirmedPayments($transaction, $payMongo, $pos);
            if ($saved > 0) {
                return redirect()->route('pay.show', $token)->with('status', 'Thank you! Your payment of ₱' . number_format($saved, 2) . ' was received.');
            }
        }

        return redirect()->route('pay.show', $token)
            ->with('status', 'We have not received the payment yet. If you already paid, please wait a minute and refresh this page, or show your PayMongo receipt to the cashier.');
    }

    // Saves every PayMongo payment of the bill's checkout page that is not saved yet. Returns the pesos saved.
    private function saveConfirmedPayments(Transaction $transaction, PayMongo $payMongo, PosService $pos): float
    {
        $saved = 0.0;

        foreach ($payMongo->paidPayments($transaction->paymongo_checkout_id) as $paid) {
            try {
                $isNew = DB::transaction(function () use ($transaction, $paid, $pos) {
                    // Locked, so the customer's page and the cashier's "Check" cannot save it twice at the same time
                    Transaction::lockForUpdate()->find($transaction->id);

                    // The same PayMongo payment is saved only once, even if the page is opened again
                    if (Payment::where('method', Payment::ONLINE)->where('reference_number', $paid['id'])->exists()) {
                        return false;
                    }

                    $pos->addPayment($transaction, [
                        'amount' => $paid['amount'],
                        'method' => Payment::ONLINE,
                        'reference_number' => $paid['id'],
                    ], null);

                    return true;
                });
                if (! $isNew) {
                    continue;
                }
                $saved += $paid['amount'];
                ActivityLog::record('payment', 'POS', 'Received ₱' . number_format($paid['amount'], 2) . ' online (PayMongo ' . $paid['id'] . ') for ' . $transaction->receipt_number . '.', $transaction);
            } catch (ValidationException $e) {
                // e.g. the cashier collected cash while the customer was paying online: needs a refund check
                ActivityLog::record('payment', 'POS', 'CHECK: PayMongo payment ' . $paid['id'] . ' (₱' . number_format($paid['amount'], 2) . ') for ' . $transaction->receipt_number . ' was not saved: ' . collect($e->errors())->flatten()->first(), $transaction);
            }
        }

        return $saved;
    }

    private function findByToken(string $token): Transaction
    {
        abort_unless(strlen($token) === 40, 404);

        return Transaction::with(['customer', 'pet', 'items', 'payments'])->where('pay_token', $token)->firstOrFail();
    }

    // Demo pages open only in demo mode, for a bill that was sent to the practice page
    private function findDemoBill(string $token): Transaction
    {
        abort_unless(PayMongo::demo(), 404);
        $transaction = $this->findByToken($token);
        abort_unless(str_starts_with((string) $transaction->paymongo_checkout_id, 'cs_demo_'), 404);

        return $transaction;
    }

    private function ensureEnabled(): void
    {
        if (! PayMongo::enabled()) {
            throw ValidationException::withMessages(['paymongo' => 'Online payment is turned off. Add PAYMONGO_SECRET_KEY to the .env file first.']);
        }
    }
}
