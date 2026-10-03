<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\PatientVisit;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\PatientFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Patient flow board (capstone FR-REQ012 / FR-REQ013): Waiting -> Ongoing -> Completed (or Cancelled).
 * Staff (/staff/patient-flow) update the status; the Vet/Admin (/admin/patient-flow) views it.
 * It is an internal board only: no messages are sent to customers.
 */
class PatientFlowController extends Controller
{
    public function index(Request $request): View
    {
        $date = $this->date($request);
        $purpose = in_array($request->query('purpose'), Service::PURPOSES, true) ? $request->query('purpose') : null;

        $visits = PatientVisit::with(['customer', 'pet', 'service', 'veterinarian', 'appointment'])
            ->whereDate('visit_date', $date)
            ->when($purpose, fn ($q) => $q->whereHas('service', fn ($s) => $s->where('purpose', $purpose)))
            ->orderBy('queue_number')
            ->get();

        // Today's bookings that have not arrived yet (shown only for today)
        $toCheckIn = $date->isToday()
            ? Appointment::with(['customer', 'pet', 'service'])
                ->whereDate('appointment_date', $date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereNotIn('id', PatientVisit::whereNotNull('appointment_id')->select('appointment_id'))
                ->when($purpose, fn ($q) => $q->whereHas('service', fn ($s) => $s->where('purpose', $purpose)))
                ->orderBy('appointment_time')
                ->get()
            : collect();

        $data = [
            'area' => $this->area($request),
            'date' => $date,
            'purpose' => $purpose,
            'columns' => collect(array_keys(PatientFlow::NEXT))->mapWithKeys(fn ($s) => [$s => $visits->where('status', $s)]),
            'toCheckIn' => $toCheckIn,
            'vets' => User::whereHas('role', fn ($q) => $q->where('slug', Role::VET_ADMIN))->where('status', 'active')->orderBy('last_name')->get(),
        ];

        // The page refreshes the board by itself every 30 seconds and asks only for the board part
        if ($request->boolean('partial')) {
            return view('partials.patient-flow.board', $data);
        }

        return view($data['area'] . '.patient-flow', $data);
    }

    public function updateStatus(Request $request, PatientVisit $visit, PatientFlow $flow): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:ongoing,completed,cancelled'],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
            'veterinarian_id' => ['nullable', 'exists:users,id'],
        ]);

        $vetId = isset($data['veterinarian_id'])
            && User::whereKey($data['veterinarian_id'])->whereHas('role', fn ($q) => $q->where('slug', Role::VET_ADMIN))->exists()
            ? (int) $data['veterinarian_id'] : null;

        $flow->changeStatus($visit, $data['status'], $data['cancel_reason'] ?? null, $vetId);

        return back()->with('status', 'Queue #' . $visit->queue_number . ' (' . $visit->pet->name . ') is now ' . $data['status'] . '.');
    }

    public function checkInAppointment(Request $request, Appointment $appointment, PatientFlow $flow): RedirectResponse
    {
        $visit = $flow->checkInAppointment($appointment, $request->user()->id);

        return back()->with('status', $appointment->pet->name . ' was checked in as queue #' . $visit->queue_number . '.');
    }

    // ---------- helpers ----------

    private function date(Request $request): Carbon
    {
        try {
            return $request->filled('date') ? Carbon::createFromFormat('Y-m-d', $request->query('date'))->startOfDay() : today();
        } catch (\Throwable) {
            return today();
        }
    }

    // 'staff' or 'admin', taken from the address that was opened
    private function area(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'admin.') ? 'admin' : 'staff';
    }
}
