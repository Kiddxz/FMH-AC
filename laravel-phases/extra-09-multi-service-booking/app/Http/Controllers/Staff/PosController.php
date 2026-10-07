<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Transaction;
use App\Services\PosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Point of Sale for the cashier (Staff, decision P2). Capstone FR-REQ018 / FR-REQ019, SCOPE-08 to SCOPE-10.
 * A bill can be for:
 *  - a visit on the patient-flow board (walk-in)     /staff/pos?visit=5
 *  - an appointment                                    /staff/pos?appointment=3
 *  - a counter sale (e.g. a bag of dog food only)     /staff/pos
 */
class PosController extends Controller
{
    public function create(Request $request): View
    {
        $visit = $request->integer('visit') ? PatientVisit::with(['customer', 'pet', 'service', 'appointment'])->find($request->integer('visit')) : null;
        $appointment = $visit?->appointment
            ?? ($request->integer('appointment') ? Appointment::with(['customer', 'pet', 'service'])->find($request->integer('appointment')) : null);
        $source = $visit ?? $appointment;

        // The service of the visit/appointment is put on the bill already
        $firstLines = $appointment ? $appointment->serviceList()->map(fn ($s) => ['item' => 'service:' . $s->id, 'quantity' => 1])->values()->all() : ($source?->service_id ? [['item' => 'service:' . $source->service_id, 'quantity' => 1]] : [['item' => '', 'quantity' => 1]]);

        return view('staff.pos.create', [
            'visit' => $visit,
            'appointment' => $appointment,
            'billed' => $this->existingBill($visit?->id, $appointment?->id),
            'customers' => Customer::orderBy('last_name')->orderBy('first_name')->get(),
            'pets' => Pet::where('status', 'active')->orderBy('name')->get(['id', 'name', 'customer_id', 'species']),
            'selectedCustomer' => $source?->customer_id ?? ($request->integer('customer') ?: null),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'products' => InventoryItem::where('is_active', true)->whereNotNull('selling_price')
                ->withSum(['batches as stock' => fn ($q) => $q->where(fn ($b) => $b->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()))], 'quantity')
                ->orderBy('name')->get(),
            'firstLines' => $firstLines,
        ]);
    }

    public function store(Request $request, PosService $pos): RedirectResponse
    {
        $data = $request->validate([
            'patient_visit_id' => ['nullable', 'integer', 'exists:patient_visits,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'pet_id' => ['nullable', 'integer', Rule::exists('pets', 'id')->whereNull('deleted_at')],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.item' => ['required', 'regex:/^(service|product):\d+$/'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'method' => ['nullable', Rule::in(Payment::METHODS)],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ], [
            'items.required' => 'Add at least one service or product.',
            'items.*.item.required' => 'Choose a service or product on every line (or remove the empty line).',
            'items.*.item.regex' => 'Choose a service or product on every line.',
        ], [
            'items.*.quantity' => 'quantity',
        ]);

        $payment = ($data['amount'] ?? 0) > 0 ? self::checkPayment($data) : null;
        $bill = $this->billFor($data);

        $lines = collect($data['items'])->map(function ($line) {
            [$type, $id] = explode(':', $line['item']);

            return ['type' => $type, 'id' => (int) $id, 'quantity' => (int) $line['quantity']];
        })->all();

        $transaction = $pos->createBill($bill, $lines, $payment, $request->user()->id);

        ActivityLog::record('created', 'POS', 'Created bill ' . $transaction->receipt_number . ' (₱' . number_format($transaction->total, 2) . ', ' . $transaction->status . ').', $transaction);

        return redirect()->route('staff.transactions.show', $transaction)
            ->with('status', $transaction->receipt_number . ' was saved. ' . ($transaction->balance > 0 ? 'Balance: ₱' . number_format($transaction->balance, 2) . '.' : 'Fully paid.'));
    }

    // Shared with "Collect Payment": the method is required, and GCash / Maya need their reference number
    public static function checkPayment(array $data): array
    {
        if (empty($data['method'])) {
            throw ValidationException::withMessages(['method' => 'Choose how the customer paid.']);
        }
        if (in_array($data['method'], ['gcash', 'maya'], true) && blank($data['reference_number'] ?? null)) {
            throw ValidationException::withMessages(['reference_number' => 'Enter the GCash / Maya reference number.']);
        }

        return $data;
    }

    // Customer, pet, links and type of the bill. A visit or appointment decides the owner and pet.
    private function billFor(array $data): array
    {
        $visit = isset($data['patient_visit_id']) ? PatientVisit::find($data['patient_visit_id']) : null;
        $appointment = $visit?->appointment ?? (isset($data['appointment_id']) ? Appointment::find($data['appointment_id']) : null);

        if ($visit?->status === 'cancelled' || $appointment?->status === 'cancelled') {
            throw ValidationException::withMessages(['items' => 'A cancelled visit or appointment cannot be billed.']);
        }
        if ($billed = $this->existingBill($visit?->id, $appointment?->id)) {
            throw ValidationException::withMessages(['items' => 'This ' . ($visit ? 'visit' : 'appointment') . ' already has bill ' . $billed->receipt_number . '. Collect the balance there, or ask the vet to void it first.']);
        }

        if ($source = $visit ?? $appointment) {
            return [
                'customer_id' => $source->customer_id,
                'pet_id' => $source->pet_id,
                'appointment_id' => $appointment?->id,
                'patient_visit_id' => $visit?->id,
                'transaction_type' => $appointment ? 'appointment' : 'walk_in',
                'discount' => $data['discount'] ?? 0,
            ];
        }

        // Counter sale: the customer is optional, the pet must belong to that customer
        $customerId = isset($data['customer_id']) ? (int) $data['customer_id'] : null;
        $petId = isset($data['pet_id']) ? (int) $data['pet_id'] : null;
        if ($petId && (int) Pet::whereKey($petId)->value('customer_id') !== $customerId) {
            throw ValidationException::withMessages(['pet_id' => 'This pet does not belong to the chosen customer.']);
        }

        return [
            'customer_id' => $customerId,
            'pet_id' => $petId,
            'appointment_id' => null,
            'patient_visit_id' => null,
            'transaction_type' => 'counter',
            'discount' => $data['discount'] ?? 0,
        ];
    }

    // A visit or appointment is billed only once (a voided bill does not count)
    private function existingBill(?int $visitId, ?int $appointmentId): ?Transaction
    {
        if (! $visitId && ! $appointmentId) {
            return null;
        }

        return Transaction::where('status', '!=', 'void')
            ->where(fn ($q) => $q
                ->when($visitId, fn ($w) => $w->orWhere('patient_visit_id', $visitId))
                ->when($appointmentId, fn ($w) => $w->orWhere('appointment_id', $appointmentId)))
            ->first();
    }
}
