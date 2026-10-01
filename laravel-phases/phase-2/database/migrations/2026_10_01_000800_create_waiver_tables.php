<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Digital Waiver and Consent Forms (capstone REQ022 - REQ024)
|--------------------------------------------------------------------------
| waiver_templates : the form text for each type (operation, refusal of treatment,
|                    health certificate, major procedure - from the client interview)
| waivers          : forms prepared for a customer/pet and signed at the clinic or portal.
|                    content_snapshot keeps the exact text that was signed.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiver_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('waiver_type', ['operation', 'refusal_of_treatment', 'health_certificate', 'major_procedure', 'other']);
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('waivers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();          // e.g. WVR-000001
            $table->foreignId('waiver_template_id')->constrained('waiver_templates')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('pet_id')->constrained('pets')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('patient_visit_id')->nullable()->constrained('patient_visits')->nullOnDelete();
            $table->longText('content_snapshot');
            $table->enum('status', ['pending', 'signed', 'reviewed'])->default('pending');
            $table->string('signer_name')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->enum('signed_via', ['clinic', 'portal'])->nullable();
            $table->string('signer_ip', 45)->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waivers');
        Schema::dropIfExists('waiver_templates');
    }
};
