<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix: before Extra 55, cancelling a checked-in pet on the patient flow board did not cancel
 * its appointment, so the pet owner still saw "Confirmed". This cancels those appointments now,
 * with the same reason that was given on the board.
 */
return new class extends Migration
{
    public function up(): void
    {
        $visits = DB::table('patient_visits')
            ->join('appointments', 'appointments.id', '=', 'patient_visits.appointment_id')
            ->where('patient_visits.status', 'cancelled')
            ->whereIn('appointments.status', ['pending', 'confirmed'])
            ->select('appointments.id', 'patient_visits.cancel_reason')
            ->get();

        foreach ($visits as $visit) {
            DB::table('appointments')->where('id', $visit->id)->update([
                'status' => 'cancelled',
                'cancel_reason' => $visit->cancel_reason,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // nothing to undo: the appointments really were cancelled at the clinic
    }
};
