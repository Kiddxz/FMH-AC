<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Walk-in and Patient Flow Monitoring (capstone REQ011 - REQ013)
|--------------------------------------------------------------------------
| One row = one pet visiting the clinic today (walk-in or from an appointment).
| status follows the paper: waiting, ongoing, completed, cancelled.
| This is an internal monitoring board only (no customer notifications).
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_visits', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date');
            $table->unsignedInteger('queue_number');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('pet_id')->constrained('pets')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained('appointments')->nullOnDelete();
            $table->enum('visit_type', ['walk_in', 'appointment']);
            $table->enum('status', ['waiting', 'ongoing', 'completed', 'cancelled'])->default('waiting');
            $table->foreignId('veterinarian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['visit_date', 'queue_number']);   // queue numbers restart every day
            $table->index(['visit_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_visits');
    }
};
