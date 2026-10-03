<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\PosController;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\PosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Transaction records (capstone FR-REQ020): every bill with its payments.
 *  - Staff (/staff/transactions): view, collect the remaining balance, print receipts
 *  - Vet/Admin (/admin/transactions): view, void with a reason
 *  - Super Admin (/superadmin/transactions): view only (decision P1)
 * A saved bill is never edited or deleted. A mistake is fixed by voiding it (the stock goes back).
 */
class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Transaction::STATUSES)],
            'method' => ['nullable', Rule::in(Payment::METHODS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ], ['to.after_or_equal' => 'The "to" date must be the same as or after the "from" date.']);

        $search = trim((string) ($filters['search'] ?? ''));

        $transactions = Transaction::with(['customer', 'pet', 'payments'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['method'] ?? null, fn ($q, $method) => $q->whereHas('payments', fn ($p) => $p->where('method', $method)))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('receipt_number', 'like', "%{$search}%")
                ->orWhereHas('pet', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))))
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Payments received today on bills that are not void
        $todayPayments = Payment::whereDate('paid_at', today())->whereHas('transaction', fn ($q) => $q->where('status', '!=', 'void'));

        return view('transactions.index', [
            'area' => $this->area($request),
            'transactions' => $transactions,
            'filters' => $filters + ['search' => $search],
            'stats' => [
                'today' => (float) (clone $todayPayments)->sum('amount'),
                'todayCount' => (clone $todayPayments)->count(),
                'balance' => (float) Transaction::whereIn('status', ['unpaid', 'partial'])->sum('balance'),
                'balanceCount' => Transaction::whereIn('status', ['unpaid', 'partial'])->count(),
                'paid' => Transaction::where('status', 'paid')->count(),
                'void' => Transaction::where('status', 'void')->count(),
            ],
        ]);
    }

    public function show(Request $request, Transaction $transaction): View
    {
        Gate::authorize('view', $transaction);
        $transaction->load(['customer', 'pet', 'appointment', 'visit', 'cashier', 'voider', 'items', 'payments.receiver']);

        return view('transactions.show', ['area' => $this->area($request), 'transaction' => $transaction]);
    }

    // A clean page to print (or "Save as PDF") from the browser
    public function receipt(Request $request, Transaction $transaction): View
    {
        Gate::authorize('view', $transaction);
        $transaction->load(['customer', 'pet', 'cashier', 'items', 'payments']);

        return view('transactions.receipt', compact('transaction'));
    }

    // Staff: collect (part of) the remaining balance
    public function addPayment(Request $request, Transaction $transaction, PosService $pos): RedirectResponse
    {
        Gate::authorize('pay', $transaction);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $payment = $pos->addPayment($transaction, PosController::checkPayment($data), $request->user()->id);
        $transaction->refresh();
        ActivityLog::record('payment', 'POS', 'Received ₱' . number_format($payment->amount, 2) . ' (' . strtoupper($payment->method) . ') for ' . $transaction->receipt_number . '.', $transaction);

        return back()->with('status', 'Payment of ₱' . number_format($payment->amount, 2) . ' saved.'
            . ($payment->change_given > 0 ? ' Change: ₱' . number_format($payment->change_given, 2) . '.' : '')
            . ($transaction->balance > 0 ? ' Balance: ₱' . number_format($transaction->balance, 2) . '.' : ' Fully paid.'));
    }

    // Vet/Admin: void with a reason (the products go back to stock)
    public function void(Request $request, Transaction $transaction, PosService $pos): RedirectResponse
    {
        Gate::authorize('void', $transaction);
        $data = $request->validate(['void_reason' => ['required', 'string', 'min:5', 'max:500']], [
            'void_reason.required' => 'Please give the reason for voiding this bill.',
        ]);

        $pos->void($transaction, $data['void_reason'], $request->user()->id);
        ActivityLog::record('voided', 'POS', 'Voided ' . $transaction->receipt_number . ': ' . $data['void_reason'], $transaction);

        return back()->with('status', $transaction->receipt_number . ' was voided. Products on it were returned to stock.');
    }

    // 'staff', 'admin' or 'superadmin', taken from the address that was opened
    private function area(Request $request): string
    {
        return explode('.', (string) $request->route()->getName())[0];
    }
}
