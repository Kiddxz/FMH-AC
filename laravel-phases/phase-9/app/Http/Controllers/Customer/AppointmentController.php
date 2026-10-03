<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Services\AppointmentScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Online booking and appointment history for pet owners (capstone SCOPE-01 to SCOPE-04, FR-REQ006).
 * New bookings start as "Pending" until the clinic confirms them.
 * No reminders or queue messages are sent to customers (paper Limitations).
 */
class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $appointments = $this->customer($request)->appointments()
            ->with(['pet', 'service'])
            ->orderByDesc('appointment_date')->orderByDesc('appointment_time')
            ->get();

        [$upcoming, $past] = $appointments->partition(fn ($a) => in_array($a->status, AppointmentScheduler::ACTIVE, true)
            && $a->appointment_date->gte(today()));

        return view('customer.appointments.index', [
            'upcoming' => $upcoming->sortBy(fn ($a) => $a->appointment_date->format('Y-m-d') . $a->appointment_time)->values(),
            'past' => $past->values(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('customer.appointments.create', [
            'pets' => $this->customer($request)->pets()->where('status', 'active')->orderBy('name')->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'selectedPet' => $request->integer('pet') ?: null,
        ]);
    }

    public function store(Request $request, AppointmentScheduler $scheduler): RedirectResponse
    {
        Gate::authorize('create', Appointment::class);
        $customer = $this->customer($request);

        $data = $request->validate([
            // only the customer's own active pets and active services can be chosen
            'pet_id' => ['required', Rule::exists('pets', 'id')->where('customer_id', $customer->id)->where('status', 'active')->whereNull('deleted_at')],
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'appointment_date' => ['required', 'date_format:Y-m-d'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'pet_id.exists' => 'Please choose one of your registered pets.',
            'service_id.exists' => 'Please choose an available service.',
            'appointment_time.required' => 'Please choose a time slot.',
        ], [
            'pet_id' => 'pet', 'service_id' => 'service', 'appointment_date' => 'date', 'appointment_time' => 'time',
        ]);

        $appointment = new Appointment([
            'pet_id' => $data['pet_id'],
            'service_id' => $data['service_id'],
            'reason' => $data['reason'] ?? null,
        ]);
        $appointment->customer_id = $customer->id;   // always the logged-in owner
        $appointment->status = 'pending';
        $appointment->created_by = $request->user()->id;

        $scheduler->book($appointment, $data['appointment_date'], $data['appointment_time']);

        ActivityLog::record('created', 'Appointments', "Booked {$appointment->reference} for {$appointment->pet->name} on "
            . $appointment->appointment_date->format('M j, Y') . ' ' . Carbon::parse($appointment->appointment_time)->format('g:i A') . '.', $appointment);

        return redirect()->route('portal.appointments.show', $appointment)
            ->with('status', 'Your appointment ' . $appointment->reference . ' was booked. The clinic will confirm it.');
    }

    public function show(Appointment $appointment): View
    {
        Gate::authorize('view', $appointment);
        $appointment->load(['pet', 'service', 'veterinarian', 'customer']);

        return view('customer.appointments.show', compact('appointment'));
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('cancel', $appointment);

        $data = $request->validate(['cancel_reason' => ['required', 'string', 'max:500']], [
            'cancel_reason.required' => 'Please tell the clinic why you are cancelling.',
        ]);

        $appointment->status = 'cancelled';
        $appointment->cancel_reason = $data['cancel_reason'];
        $appointment->save();

        ActivityLog::record('cancelled', 'Appointments', "Customer cancelled {$appointment->reference}. Reason: {$data['cancel_reason']}", $appointment);

        return redirect()->route('portal.appointments.index')->with('status', 'Appointment ' . $appointment->reference . ' was cancelled.');
    }

    // Appointment history as a CSV file (opens in Excel)
    public function download(Request $request): StreamedResponse
    {
        $customer = $this->customer($request);
        $appointments = $customer->appointments()->with(['pet', 'service'])->orderByDesc('appointment_date')->get();

        return response()->streamDownload(function () use ($appointments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Pet', 'Service', 'Date', 'Time', 'Status', 'Reason']);
            foreach ($appointments as $a) {
                fputcsv($out, [
                    $a->reference, $a->pet?->name, $a->service?->name,
                    $a->appointment_date->format('Y-m-d'), Carbon::parse($a->appointment_time)->format('g:i A'),
                    ucfirst($a->status), $a->reason,
                ]);
            }
            fclose($out);
        }, 'appointment-history-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function customer(Request $request): Customer
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 403, 'Your account has no pet owner profile. Please contact the clinic.');

        return $customer;
    }
}
