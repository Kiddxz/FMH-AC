<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\Customer;
use App\Models\PatientVisit;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Transaction;
use App\Rules\RealEmail;
use App\Services\AppointmentScheduler;
use App\Services\PatientFlow;
use App\Services\PayMongo;
use App\Services\PosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Booking WITHOUT an account (requested by the adviser).
 *
 * A walk-in pet owner (at the clinic, no account) fills in one form: their name and mobile number,
 * their pet and the services. There is no date or time to choose: the booking gets the date and
 * time of the moment they book, and the pet goes straight to the clinic's Patient Flow board (Waiting)
 * with a queue number. So the staff no longer register walk-ins themselves.
 * Walk-in bookings can only be made while the clinic is open.
 * Everything is saved in the database like a walk-in customer (customer + pet + appointment + visit, no login).
 * The owner gets a reference number (APP-000123): with it and their mobile number they can check the booking,
 * or cancel it while still waiting, at /book/check. There is no shared "default" login, so nobody sees another
 * person's information.
 * If they register later with the same email, their record is linked to the new account (RegisterController).
 *
 * Payment: "Pay at the clinic", or "Pay online now" (GCash, Maya, card through PayMongo).
 * Online: a bill with the chosen services is made for the booking, and the owner goes to its
 * pay page (/pay/{token}) and on to PayMongo. If they do not finish, they can pay later from
 * "Check your booking". A cancelled booking voids an unpaid bill; a paid one is flagged for a refund.
 */
class GuestBookingController extends Controller
{
    // Home page of the booking website: book, or check/cancel a booking
    public function home(): View
    {
        $days = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];
        $schedules = \App\Models\ClinicSchedule::all()->keyBy('day_of_week');
        $hours = [];
        foreach ($days as $number => $label) {
            $s = $schedules->get($number);
            $text = $s && $s->is_open && $s->opens_at && $s->closes_at
                ? Carbon::parse($s->opens_at)->format('g:i A') . ' – ' . Carbon::parse($s->closes_at)->format('g:i A')
                : 'Closed';
            $last = array_key_last($hours);
            if ($last !== null && $hours[$last]['text'] === $text) {
                $hours[$last]['to'] = $label;
            } else {
                $hours[] = ['from' => $label, 'to' => $label, 'text' => $text];
            }
        }

        return view('guest.home', [
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'hours' => $hours,
            'todayText' => $this->todayText(),
            'openNow' => app(AppointmentScheduler::class)->openNow(),
            'onlinePayment' => PayMongo::enabled(),
        ]);
    }

    public function create(Request $request, AppointmentScheduler $scheduler): View
    {
        return view('guest.book', [
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'species' => Pet::SPECIES, 'selected' => in_array($request->integer('service'), Service::where('is_active', true)->pluck('id')->all(), true) ? [$request->integer('service')] : [],
            'onlinePayment' => PayMongo::enabled(),
            'openNow' => $scheduler->openNow(),
            'todayText' => $this->todayText(),
        ]);
    }

    public function store(Request $request, AppointmentScheduler $scheduler, PosService $pos, PatientFlow $flow): RedirectResponse
    {
        // A hidden field that people never fill in. Robots usually do.
        if (filled($request->input('website'))) {
            abort(422);
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact_number' => ['bail', 'required', 'regex:/^[0-9]+$/', 'starts_with:09', 'digits:11'],
            'email' => ['bail', 'nullable', 'email', 'max:255', new RealEmail()],
            'pet_name' => ['required', 'string', 'max:100'],
            'species' => ['required', Rule::in(Pet::SPECIES)],
            'breed' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'min:0', 'max:50'],
            'age_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'service_ids' => ['required', 'array', 'min:1', 'max:' . Appointment::MAX_SERVICES],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
            'reason' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', Rule::in(['clinic', 'online'])],
            'privacy' => ['accepted'],
        ], [
            'contact_number.regex' => 'The mobile number must contain numbers only (no letters, spaces or symbols).',
            'contact_number.starts_with' => 'The mobile number must start with 09 (e.g. 09171234567).',
            'contact_number.digits' => 'The mobile number must be exactly 11 digits (e.g. 09171234567).',
            'service_ids.required' => 'Please choose at least one service.',
            'service_ids.max' => 'You can choose up to ' . Appointment::MAX_SERVICES . ' services in one booking.',
            'service_ids.*.exists' => 'Please choose an available service.',
            'privacy.accepted' => 'Please allow the clinic to keep your information for this booking.',
        ], [
            'contact_number' => 'mobile number', 'pet_name' => 'pet name', 'service_ids' => 'services', 'age' => 'age (years)', 'age_months' => 'age (months)',
        ]);

        // Walk-ins book when they are at the clinic, so only during clinic hours
        if (! $scheduler->openNow()) {
            throw ValidationException::withMessages(['visit' => 'The clinic is closed right now. Walk-in bookings can only be made during clinic hours.']);
        }

        // The same person booking again (same mobile number and last name) keeps one record
        $customer = Customer::where('contact_number', $data['contact_number'])
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower(trim($data['last_name']))])
            ->first();

        $payOnline = ($data['payment_method'] ?? 'clinic') === 'online';
        if ($payOnline && ! PayMongo::enabled()) {
            throw ValidationException::withMessages(['payment_method' => 'Online payment is not available right now. Please choose "Pay at the clinic".']);
        }

        if ($customer?->user_id) {
            throw ValidationException::withMessages(['contact_number' => 'This mobile number belongs to a registered account. Please log in to book, so the booking appears in your account.']);
        }

        $appointment = DB::transaction(function () use ($data, $customer, $scheduler, $pos, $flow, $payOnline) {
            if (! $customer) {
                $customer = new Customer([
                    'first_name' => trim($data['first_name']),
                    'last_name' => trim($data['last_name']),
                    'contact_number' => $data['contact_number'],
                    'email' => $data['email'] ?? null,
                ]);
                $customer->is_walk_in = true;   // no portal account
                $customer->save();
            } elseif (! $customer->email && ! empty($data['email'])) {
                $customer->email = $data['email'];
                $customer->save();
            }

            // The same pet (same name and species) is used again, otherwise a new pet is added
            $pet = $customer->pets()->where('status', 'active')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['pet_name']))])
                ->where('species', $data['species'])->first();
            if (! $pet) {
                $pet = new Pet([
                    'name' => trim($data['pet_name']),
                    'species' => $data['species'],
                    'breed' => $data['breed'] ?? null,
                    'gender' => $data['gender'],
                    'birthdate' => Pet::birthdateFromAge((int) $data['age'], (int) ($data['age_months'] ?? 0)),   // age -> approximate birthdate
                ]);
                $pet->customer_id = $customer->id;
                $pet->save();
            }

            // The same pet cannot be in today's queue twice
            if (PatientVisit::where('pet_id', $pet->id)->whereDate('visit_date', today())->whereIn('status', ['waiting', 'ongoing'])->exists()) {
                throw ValidationException::withMessages(['pet_name' => $pet->name . ' is already in today\'s queue at the clinic.']);
            }

            // The date and time are now; the owner is at the clinic, so it is confirmed right away
            $appointment = new Appointment([
                'pet_id' => $pet->id,
                'service_id' => (int) $data['service_ids'][0],
                'reason' => $data['reason'] ?? null,
            ]);
            $appointment->customer_id = $customer->id;
            $appointment->status = 'confirmed';
            $appointment->notes = 'Walk-in booking without an account. Payment: ' . ($payOnline ? 'online (PayMongo).' : 'at the clinic.');
            $scheduler->bookNow($appointment);
            $appointment->saveServices($data['service_ids']);

            // Straight to the Patient Flow board (Waiting) with the next queue number
            $flow->checkIn([
                'customer_id' => $customer->id,
                'pet_id' => $pet->id,
                'service_id' => $appointment->service_id,
                'appointment_id' => $appointment->id,
                'visit_type' => 'walk_in',
                'notes' => $appointment->reason,
            ], null);

            // Pay online: the bill of the chosen services, with its pay link (no cashier yet)
            if ($payOnline) {
                $bill = $pos->createBill([
                    'customer_id' => $customer->id,
                    'pet_id' => $pet->id,
                    'appointment_id' => $appointment->id,
                    'transaction_type' => 'appointment',
                    'discount' => 0,
                ], collect($data['service_ids'])->map(fn ($id) => ['type' => 'service', 'id' => (int) $id, 'quantity' => 1])->all(), null, null);
                $bill->pay_token = Str::random(40);
                $bill->save();
            }

            return $appointment;
        });

        $visit = PatientVisit::where('appointment_id', $appointment->id)->first();
        ActivityLog::record('created', 'Appointments', "Walk-in booking {$appointment->reference} ({$appointment->service_names}) for "
            . $appointment->pet->name . ' of ' . $appointment->customer->full_name . ' (no account), queue #' . $visit->queue_number . '.', $appointment);

        $request->session()->put('guest_booking', $appointment->id);

        // Pay online: straight to the bill's pay page (it has the "Pay Online" button to PayMongo)
        $bill = $this->billOf($appointment);
        if ($bill && $bill->pay_token) {
            return redirect()->route('pay.show', $bill->pay_token)
                ->with('status', 'Booking ' . $appointment->reference . ' received! Your queue number is #' . $visit->queue_number . '. Pay online now, or pay at the clinic.');
        }

        return redirect()->route('guest.book.done');
    }

    // The "Thank you" page with the reference number (only right after booking)
    public function done(Request $request): View|RedirectResponse
    {
        $appointment = Appointment::with(['pet', 'customer'])->find($request->session()->get('guest_booking'));
        if (! $appointment) {
            return redirect()->route('guest.book');
        }

        return view('guest.done', [
            'appointment' => $appointment,
            'bill' => $this->billOf($appointment),
            'visit' => PatientVisit::where('appointment_id', $appointment->id)->first(),
        ]);
    }

    // ---------- Check or cancel a booking with the reference number + mobile number ----------

    public function lookupForm(): View
    {
        return view('guest.lookup');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:20'],
            'contact_number' => ['required', 'string', 'max:20'],
        ], [], ['contact_number' => 'mobile number']);

        $appointment = Appointment::where('reference', strtoupper(trim($data['reference'])))
            ->whereHas('customer', fn ($q) => $q->whereNull('user_id')->where('contact_number', trim($data['contact_number'])))
            ->first();

        if (! $appointment) {
            // Same message for a wrong reference or a wrong number, so nobody can guess bookings
            throw ValidationException::withMessages(['reference' => 'No booking matches this reference number and mobile number.']);
        }

        $request->session()->put('guest_lookup', $appointment->id);

        return redirect()->route('guest.lookup.show');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $appointment = Appointment::with(['pet', 'customer', 'veterinarian'])->find($request->session()->get('guest_lookup'));
        if (! $appointment) {
            return redirect()->route('guest.lookup');
        }

        return view('guest.show', [
            'appointment' => $appointment,
            'canCancel' => $this->canCancel($appointment),
            'bill' => $this->billOf($appointment),
            'visit' => PatientVisit::where('appointment_id', $appointment->id)->first(),
        ]);
    }

    public function cancel(Request $request, PatientFlow $flow): RedirectResponse
    {
        $appointment = Appointment::find($request->session()->get('guest_lookup'));
        abort_unless($appointment, 403);
        if (! $this->canCancel($appointment)) {
            return back()->withErrors(['cancel_reason' => 'This booking can no longer be cancelled online. Please call the clinic.']);
        }

        $data = $request->validate(['cancel_reason' => ['required', 'string', 'max:500']], [
            'cancel_reason.required' => 'Please tell the clinic why you are cancelling.',
        ]);

        $bill = $this->billOf($appointment);
        DB::transaction(function () use ($appointment, $bill, $data, $flow) {
            $appointment->status = 'cancelled';
            $appointment->cancel_reason = $data['cancel_reason'];
            $appointment->save();

            // A walk-in still waiting leaves the Patient Flow board too
            $visit = PatientVisit::where('appointment_id', $appointment->id)->first();
            if ($visit && $visit->status === 'waiting') {
                $flow->changeStatus($visit, 'cancelled', 'Cancelled by the owner: ' . $data['cancel_reason']);
            }

            // An online bill that was not paid yet is cancelled too (it has services only, no stock to return)
            if ($bill && $bill->status === 'unpaid') {
                $bill->status = 'void';
                $bill->void_reason = 'Booking ' . $appointment->reference . ' was cancelled by the customer.';
                $bill->voided_at = now();
                $bill->save();
            }
        });
        ActivityLog::record('cancelled', 'Appointments', "Guest cancelled {$appointment->reference}. Reason: {$data['cancel_reason']}", $appointment);

        $message = 'Booking ' . $appointment->reference . ' was cancelled.';
        if ($bill && (float) $bill->amount_paid > 0) {
            ActivityLog::record('payment', 'POS', 'CHECK: refund ₱' . number_format($bill->amount_paid, 2) . ' paid online for ' . $bill->receipt_number . ' (booking ' . $appointment->reference . ' was cancelled by the customer).', $bill);
            $message .= ' You paid ₱' . number_format($bill->amount_paid, 2) . ' online: the clinic will contact you about your refund.';
        }

        return redirect()->route('guest.lookup.show')->with('status', $message);
    }

    // The online bill of the booking (the newest one that is not void)
    private function billOf(Appointment $appointment): ?Transaction
    {
        return Transaction::where('appointment_id', $appointment->id)->where('status', '!=', 'void')->latest('id')->first();
    }

    // A walk-in can cancel while still waiting (not yet with the veterinarian)
    private function canCancel(Appointment $appointment): bool
    {
        if (! in_array($appointment->status, AppointmentScheduler::ACTIVE, true)) {
            return false;
        }

        $visit = PatientVisit::where('appointment_id', $appointment->id)->first();
        if ($visit) {
            return $visit->status === 'waiting';
        }

        return Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->appointment_time)->isFuture();
    }

    // Today's clinic hours (e.g. "8:00 AM – 5:00 PM"), or null when closed today
    private function todayText(): ?string
    {
        $today = ClinicSchedule::where('day_of_week', now()->dayOfWeek)->first();

        return $today && $today->is_open && $today->opens_at && $today->closes_at
            ? Carbon::parse($today->opens_at)->format('g:i A') . ' – ' . Carbon::parse($today->closes_at)->format('g:i A')
            : null;
    }
}
