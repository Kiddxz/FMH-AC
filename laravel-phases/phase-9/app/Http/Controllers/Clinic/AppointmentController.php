<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Appointment management for the clinic: Staff (/staff/appointments) and Vet/Admin (/admin/appointments).
 * The same code serves both areas; only the pages (layout) differ.
 *
 * Status rules:
 *   pending   -> confirmed or cancelled
 *   confirmed -> completed or cancelled (and can be rescheduled)
 *   completed / cancelled -> final, cannot change anymore
 */
class AppointmentController extends Controller
{
    private const NEXT = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');
        $serviceId = $request->integer('service') ?: null;
        $date = $request->query('date');

        $appointments = Appointment::with(['customer', 'pet', 'service'])
            ->when(in_array($status, Appointment::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
            ->when($date, fn ($q) => $q->whereDate('appointment_date', $date))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('pet', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))))
            // upcoming first (nearest date on top), then the past ones
            ->orderByRaw('appointment_date < ? asc', [today()->toDateString()])
            ->orderByRaw('CASE WHEN appointment_date >= ? THEN appointment_date END ASC', [today()->toDateString()])
            ->orderByDesc('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(15)
            ->withQueryString();

        return view($this->area($request) . '.appointments.index', [
            'appointments' => $appointments,
            'services' => Service::orderBy('name')->get(),
            'search' => $search,
            'status' => $status,
            'serviceId' => $serviceId,
            'date' => $date,
        ]);
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $appointment->load(['customer', 'pet', 'service', 'veterinarian', 'creator']);

        return view($this->area($request) . '.appointments.show', [
            'appointment' => $appointment,
            'next' => self::NEXT[$appointment->status],
        ]);
    }

    public function create(Request $request): View
    {
        return view($this->area($request) . '.appointments.create', $this->formData() + [
            'appointment' => new Appointment(['pet_id' => $request->integer('pet') ?: null]),
        ]);
    }

    // A booking made by the clinic (e.g. by phone) is confirmed right away
    public function store(Request $request, AppointmentScheduler $scheduler): RedirectResponse
    {
        $data = $this->validateForm($request, true);
        $pet = Pet::findOrFail($data['pet_id']);

        $appointment = new Appointment([
            'pet_id' => $pet->id,
            'service_id' => $data['service_id'],
            'veterinarian_id' => $data['veterinarian_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        $appointment->customer_id = $pet->customer_id;
        $appointment->status = 'confirmed';
        $appointment->created_by = $request->user()->id;

        $scheduler->book($appointment, $data['appointment_date'], $data['appointment_time']);
        ActivityLog::record('created', 'Appointments', "Created {$appointment->reference} for {$pet->name}.", $appointment);

        return redirect()->route($this->area($request) . '.appointments.show', $appointment)
            ->with('status', 'Appointment ' . $appointment->reference . ' was created and confirmed.');
    }

    public function edit(Request $request, Appointment $appointment): View|RedirectResponse
    {
        if (! in_array($appointment->status, AppointmentScheduler::ACTIVE, true)) {
            return redirect()->route($this->area($request) . '.appointments.show', $appointment)
                ->withErrors(['status' => 'A ' . $appointment->status . ' appointment can no longer be edited.']);
        }

        return view($this->area($request) . '.appointments.edit', $this->formData($appointment) + compact('appointment'));
    }

    public function update(Request $request, Appointment $appointment, AppointmentScheduler $scheduler): RedirectResponse
    {
        if (! in_array($appointment->status, AppointmentScheduler::ACTIVE, true)) {
            throw ValidationException::withMessages(['status' => 'A ' . $appointment->status . ' appointment can no longer be edited.']);
        }

        $data = $this->validateForm($request, false, $appointment);
        $appointment->fill([
            'service_id' => $data['service_id'],
            'veterinarian_id' => $data['veterinarian_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        $scheduler->reschedule($appointment, $data['appointment_date'], $data['appointment_time']);
        ActivityLog::record('updated', 'Appointments', "Updated {$appointment->reference}.", $appointment);

        return redirect()->route($this->area($request) . '.appointments.show', $appointment)
            ->with('status', 'Appointment ' . $appointment->reference . ' was updated.');
    }

    public function confirm(Request $request, Appointment $appointment): RedirectResponse
    {
        return $this->changeStatus($request, $appointment, 'confirmed');
    }

    public function complete(Request $request, Appointment $appointment): RedirectResponse
    {
        return $this->changeStatus($request, $appointment, 'completed');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate(['cancel_reason' => ['required', 'string', 'max:500']], [
            'cancel_reason.required' => 'Please enter the reason for cancelling.',
        ]);

        return $this->changeStatus($request, $appointment, 'cancelled', $data['cancel_reason']);
    }

    // ---------- helpers ----------

    private function changeStatus(Request $request, Appointment $appointment, string $to, ?string $reason = null): RedirectResponse
    {
        $from = $appointment->status;
        if (! in_array($to, self::NEXT[$from], true)) {
            return back()->withErrors(['status' => "A {$from} appointment cannot be marked as {$to}."]);
        }

        $appointment->status = $to;
        if ($to === 'cancelled') {
            $appointment->cancel_reason = $reason;
        }
        $appointment->save();

        ActivityLog::record($to, 'Appointments', ucfirst($to) . " {$appointment->reference} (was {$from})." . ($reason ? " Reason: {$reason}" : ''), $appointment);

        return back()->with('status', 'Appointment ' . $appointment->reference . ' is now ' . $to . '.');
    }

    private function validateForm(Request $request, bool $isNew, ?Appointment $appointment = null): array
    {
        $rules = [
            // a new appointment needs an active service; an existing one may keep its current service
            'service_id' => ['required', Rule::exists('services', 'id')->where(fn ($q) => $q
                ->where('is_active', true)->orWhere('id', $appointment?->service_id))],
            'veterinarian_id' => ['nullable', Rule::exists('users', 'id')->where('role_id', Role::where('slug', Role::VET_ADMIN)->value('id'))],
            'appointment_date' => ['required', 'date_format:Y-m-d'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
        if ($isNew) {
            $rules['pet_id'] = ['required', Rule::exists('pets', 'id')->where('status', 'active')->whereNull('deleted_at')];
        }

        return $request->validate($rules, [
            'appointment_time.required' => 'Please choose a time slot.',
        ], [
            'pet_id' => 'pet', 'service_id' => 'service', 'veterinarian_id' => 'veterinarian',
            'appointment_date' => 'date', 'appointment_time' => 'time',
        ]);
    }

    private function formData(?Appointment $appointment = null): array
    {
        return [
            'pets' => Pet::with('customer')->where('status', 'active')->orderBy('name')->get(),
            'services' => Service::where('is_active', true)->orWhere('id', $appointment?->service_id)->orderBy('name')->get(),
            'vets' => User::whereHas('role', fn ($q) => $q->where('slug', Role::VET_ADMIN))->where('status', 'active')->orderBy('last_name')->get(),
        ];
    }

    // 'staff' or 'admin', taken from the address that was opened (staff.appointments... / admin.appointments...)
    private function area(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'admin.') ? 'admin' : 'staff';
    }
}
