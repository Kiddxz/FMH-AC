<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Digital Pet Records (capstone REQ007 - REQ010)
|--------------------------------------------------------------------------
| medical_records   : consultation / treatment / vaccination / grooming record
|                     (diagnosis is TYPED by the veterinarian - no automated diagnosis)
| prescriptions     : medicines prescribed in a record
| treatments        : procedures done in a record
| vaccinations      : vaccine history with next due date
| care_instructions : post-treatment care instructions; customers only see them
|                     after the vet releases them
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets')->restrictOnDelete();
            $table->foreignId('patient_visit_id')->nullable()->constrained('patient_visits')->nullOnDelete();
            $table->foreignId('veterinarian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('record_type', ['consultation', 'treatment', 'vaccination', 'grooming']);
            $table->date('record_date');
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('findings')->nullable();
            $table->text('diagnosis')->nullable();          // entered by the veterinarian
            $table->text('notes')->nullable();              // full medical notes (not shown to customers)
            $table->timestamps();
            $table->index(['pet_id', 'record_date']);
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('medicine_name');
            $table->string('dosage');
            $table->string('frequency');
            $table->string('duration')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->string('procedure_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets')->restrictOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->string('vaccine_name');
            $table->string('batch_number')->nullable();
            $table->date('date_given');
            $table->date('next_due_date')->nullable();
            $table->foreignId('given_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('next_due_date');
        });

        Schema::create('care_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained('pets')->restrictOnDelete();
            $table->string('title');
            $table->text('instructions');
            $table->boolean('is_released')->default(false);  // customer sees it only when released
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_instructions');
        Schema::dropIfExists('vaccinations');
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medical_records');
    }
};
