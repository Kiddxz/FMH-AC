<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| More than one service in one appointment
|--------------------------------------------------------------------------
| appointment_service : every service chosen for an appointment (e.g. Consultation + Vaccination)
| appointments.service_id stays as the FIRST (main) service, so the patient-flow board,
| filters and older pages keep working.
| Old appointments are copied in, so each one has its service in the new table too.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['appointment_id', 'service_id']);
        });

        $now = now();
        DB::table('appointments')->whereNotNull('service_id')->orderBy('id')
            ->each(function ($appointment) use ($now) {
                DB::table('appointment_service')->insert([
                    'appointment_id' => $appointment->id,
                    'service_id' => $appointment->service_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_service');
    }
};
