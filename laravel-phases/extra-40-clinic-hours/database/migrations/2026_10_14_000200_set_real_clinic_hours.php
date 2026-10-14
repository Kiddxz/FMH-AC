<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The clinic's real hours (all branches):
 *   Monday - Saturday  8:00 AM - 7:00 PM
 *   Sunday             8:00 AM - 3:00 PM
 * The home page, the walk-in website, the time slots and the "Open now" badge all read these hours.
 * They can still be changed later in Super Admin -> Settings -> Clinic Hours.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clinic_schedules')) {
            return;
        }

        for ($day = 0; $day <= 6; $day++) {
            $hours = [
                'is_open' => true,
                'opens_at' => '08:00:00',
                'closes_at' => $day === 0 ? '15:00:00' : '19:00:00',   // 0 = Sunday
                'updated_at' => now(),
            ];

            if (DB::table('clinic_schedules')->where('day_of_week', $day)->exists()) {
                DB::table('clinic_schedules')->where('day_of_week', $day)->update($hours);
            } else {
                DB::table('clinic_schedules')->insert($hours + ['day_of_week' => $day, 'slot_minutes' => 30, 'max_per_slot' => 3, 'created_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // the old demo hours: Monday - Saturday 8:00 AM - 5:00 PM, closed on Sunday
        if (! Schema::hasTable('clinic_schedules')) {
            return;
        }
        DB::table('clinic_schedules')->where('day_of_week', '!=', 0)->update(['is_open' => true, 'opens_at' => '08:00:00', 'closes_at' => '17:00:00']);
        DB::table('clinic_schedules')->where('day_of_week', 0)->update(['is_open' => false, 'opens_at' => null, 'closes_at' => null]);
    }
};
