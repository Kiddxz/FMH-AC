<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\ClinicSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Time slots and safe booking (capstone SCOPE-01 to SCOPE-04).
 *
 * - Slots come from the clinic hours (clinic_schedules), e.g. Mon-Sat 8:00-17:00 every 30 minutes.
 * - Each slot takes at most "max_per_slot" appointments (pending + confirmed).
 * - The same pet can never have two active appointments at the same date and time.
 * - Saving happens inside a database transaction with a row lock, so two people
 *   clicking "Book" at the same moment cannot both take the last place (no double booking).
 */
class AppointmentScheduler
{
    public const ACTIVE = ['pending', 'confirmed'];

    // All slots of one day: [['time' => '08:00', 'label' => '8:00 AM', 'remaining' => 3, 'available' => true], ...]
    public function slotsFor(Carbon|string $date, ?int $ignoreAppointmentId = null): array
    {
        $date = Carbon::parse($date)->startOfDay();
        $schedule = ClinicSchedule::where('day_of_week', $date->dayOfWeek)->first();

        if (! $schedule || ! $schedule->is_open || ! $schedule->opens_at || ! $schedule->closes_at) {
            return [];
        }

        $taken = Appointment::whereDate('appointment_date', $date)
            ->whereIn('status', self::ACTIVE)
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->get(['appointment_time'])
            ->countBy(fn ($a) => substr($a->appointment_time, 0, 5));

        $slots = [];
        $time = $date->copy()->setTimeFromTimeString($schedule->opens_at);
        $close = $date->copy()->setTimeFromTimeString($schedule->closes_at);

        while ($time->lt($close)) {
            $key = $time->format('H:i');
            $remaining = max(0, $schedule->max_per_slot - ($taken[$key] ?? 0));
            $slots[] = [
                'time' => $key,
                'label' => $time->format('g:i A'),
                'remaining' => $remaining,
                'available' => $remaining > 0 && $time->isFuture(),
            ];
            $time->addMinutes($schedule->slot_minutes);
        }

        return $slots;
    }

    // Check the date/time and save the appointment safely. Returns the saved appointment.
    public function book(Appointment $appointment, string $date, string $time): Appointment
    {
        return DB::transaction(function () use ($appointment, $date, $time) {
            $time = substr($time, 0, 5);
            $this->assertBookable($date, $time, $appointment->pet_id, $appointment->id);

            $isNew = ! $appointment->exists;
            $appointment->appointment_date = $date;
            $appointment->appointment_time = $time . ':00';
            if ($isNew) {
                $appointment->reference = 'TMP-' . uniqid();   // replaced below with APP-000123
            }
            $appointment->save();

            if ($isNew) {
                $appointment->reference = 'APP-' . str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT);
                $appointment->save();
            }

            return $appointment;
        });
    }

    // Move an existing appointment to a new date / time (status stays the same)
    public function reschedule(Appointment $appointment, string $date, string $time): Appointment
    {
        $old = $appointment->appointment_date->format('M j, Y') . ' ' . Carbon::parse($appointment->appointment_time)->format('g:i A');
        $this->book($appointment, $date, $time);
        $new = $appointment->appointment_date->format('M j, Y') . ' ' . Carbon::parse($appointment->appointment_time)->format('g:i A');

        if ($old !== $new) {
            ActivityLog::record('rescheduled', 'Appointments', "Rescheduled {$appointment->reference} from {$old} to {$new}.", $appointment);
        }

        return $appointment;
    }

    private function assertBookable(string $date, string $time, ?int $petId, ?int $ignoreId): void
    {
        $day = Carbon::parse($date)->startOfDay();
        $slot = collect($this->slotsFor($day, $ignoreId))->firstWhere('time', $time);

        if ($day->lt(today())) {
            $this->fail('appointment_date', 'Please choose today or a future date.');
        }
        if ($day->gt(today()->addMonths(3))) {
            $this->fail('appointment_date', 'Appointments can be booked up to 3 months ahead.');
        }
        if (! ClinicSchedule::where('day_of_week', $day->dayOfWeek)->where('is_open', true)->exists()) {
            $this->fail('appointment_date', 'The clinic is closed on ' . $day->format('l') . 's. Please choose another day.');
        }
        if (! $slot) {
            $this->fail('appointment_time', 'Please choose one of the available time slots.');
        }
        if (! $day->copy()->setTimeFromTimeString($time)->isFuture()) {
            $this->fail('appointment_time', 'That time has already passed. Please choose a later time.');
        }

        // Lock the rows of this slot while counting, so two bookings cannot pass at the same moment
        $schedule = ClinicSchedule::where('day_of_week', $day->dayOfWeek)->first();
        $inSlot = Appointment::whereDate('appointment_date', $day)
            ->where('appointment_time', $time . ':00')
            ->whereIn('status', self::ACTIVE)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->lockForUpdate()
            ->get(['id', 'pet_id']);

        if ($inSlot->count() >= $schedule->max_per_slot) {
            $this->fail('appointment_time', 'This time slot is already full. Please choose another time.');
        }
        if ($petId && $inSlot->contains('pet_id', $petId)) {
            $this->fail('appointment_time', 'This pet already has an appointment at that date and time.');
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
