<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\PatientVisit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Walk-in and patient flow monitoring (capstone FR-REQ011 - FR-REQ013).
 * Internal board only: nothing is sent to the customer (paper Limitations, decision P8).
 *
 * Status rules:
 *   waiting -> ongoing or cancelled
 *   ongoing -> completed or cancelled
 *   completed / cancelled -> final
 */
class PatientFlow
{
    public const NEXT = [
        'waiting' => ['ongoing', 'cancelled'],
        'ongoing' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    /**
     * Put a pet on today's board with the next queue number.
     * $data: customer_id, pet_id, service_id, visit_type, appointment_id?, veterinarian_id?, notes?
     */
    public function checkIn(array $data, int $staffId): PatientVisit
    {
        // Two people may check in at the same moment: the database refuses a repeated queue
        // number (unique visit_date + queue_number), so we simply try again with the next one.
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($data, $staffId) {
                    $next = (int) PatientVisit::whereDate('visit_date', today())->lockForUpdate()->max('queue_number') + 1;

                    $visit = new PatientVisit($data);
                    $visit->visit_date = today();
                    $visit->queue_number = $next;
                    $visit->checked_in_at = now();
                    $visit->handled_by = $staffId;
                    $visit->save();

                    return $visit;
                });
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    // A booked appointment arrives at the clinic
    public function checkInAppointment(Appointment $appointment, int $staffId): PatientVisit
    {
        if (! $appointment->appointment_date->isToday()) {
            throw ValidationException::withMessages(['visit' => 'Only appointments scheduled for today can be checked in.']);
        }
        if (! in_array($appointment->status, ['pending', 'confirmed'], true)) {
            throw ValidationException::withMessages(['visit' => 'This appointment is already ' . $appointment->status . '.']);
        }
        if (PatientVisit::where('appointment_id', $appointment->id)->exists()) {
            throw ValidationException::withMessages(['visit' => 'This appointment is already on the patient flow board.']);
        }

        // Arriving for a pending booking confirms it
        if ($appointment->status === 'pending') {
            $appointment->status = 'confirmed';
            $appointment->save();
        }

        $visit = $this->checkIn([
            'customer_id' => $appointment->customer_id,
            'pet_id' => $appointment->pet_id,
            'service_id' => $appointment->service_id,
            'appointment_id' => $appointment->id,
            'visit_type' => 'appointment',
            'veterinarian_id' => $appointment->veterinarian_id,
            'notes' => $appointment->reason,
        ], $staffId);

        ActivityLog::record('checked_in', 'Patient Flow', 'Checked in ' . $appointment->reference . ' (' . $appointment->pet?->name . ') as queue #' . $visit->queue_number . '.', $visit);

        return $visit;
    }

    public function changeStatus(PatientVisit $visit, string $status, ?string $reason = null, ?int $vetId = null): void
    {
        if (! in_array($status, self::NEXT[$visit->status], true)) {
            throw ValidationException::withMessages([
                'status' => 'A ' . $visit->status . ' patient cannot be moved to ' . $status . '.',
            ]);
        }

        if ($status === 'cancelled' && blank($reason)) {
            throw ValidationException::withMessages(['cancel_reason' => 'Please give a reason for cancelling.']);
        }

        DB::transaction(function () use ($visit, $status, $reason, $vetId) {
            $visit->status = $status;

            match ($status) {
                'ongoing' => $visit->started_at = now(),
                'completed' => $visit->completed_at = now(),
                'cancelled' => $visit->cancelled_at = now(),
            };

            if ($status === 'ongoing' && $vetId) {
                $visit->veterinarian_id = $vetId;
            }
            if ($status === 'cancelled') {
                $visit->cancel_reason = $reason;
            }
            $visit->save();

            // Finishing the visit also finishes the appointment it came from
            if ($status === 'completed' && $visit->appointment && $visit->appointment->status === 'confirmed') {
                $visit->appointment->status = 'completed';
                $visit->appointment->save();
            }
        });

        ActivityLog::record($status, 'Patient Flow', 'Queue #' . $visit->queue_number . ' (' . $visit->pet?->name . ') is now ' . $status
            . ($status === 'cancelled' ? ': ' . $reason : '') . '.', $visit);
    }
}
