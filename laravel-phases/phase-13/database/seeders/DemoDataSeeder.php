<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\CareInstruction;
use App\Models\Customer;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\Waiver;
use App\Models\WaiverTemplate;
use Illuminate\Database\Seeder;

/**
 * FICTITIOUS sample data for testing (the client interview recommends fictitious records).
 * Uses the same sample names as the original HTML pages.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Run once only: stop if the sample pets already exist
        if (Pet::where('name', 'Max')->exists()) {
            return;
        }

        $vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $service = fn (string $name) => Service::where('name', $name)->value('id');

        // ---------- Customers (Mark Santos already exists: he is the demo customer account) ----------
        $mark = Customer::where('email', 'owner@fmhanimalclinic.com')->first();

        $others = [
            ['John', 'Cruz', '09181234567', 'johncruz@email.com', false],
            ['Anna', 'Reyes', '09191234567', 'annareyes@email.com', false],
            ['Maria', 'Lopez', '09201234567', 'marialopez@email.com', true],
            ['James', 'Garcia', '09211234567', 'jamesgarcia@email.com', true],
            ['Sarah', 'Garcia', '09221234567', 'sarahgarcia@email.com', false],
        ];

        $customers = ['Mark' => $mark];
        foreach ($others as [$first, $last, $contact, $email, $walkIn]) {
            $customers[$first] = Customer::create([
                'first_name' => $first,
                'last_name' => $last,
                'contact_number' => $contact,
                'email' => $email,
                'address' => 'Las Piñas City',
                'is_walk_in' => $walkIn,
            ]);
        }

        // ---------- Pets ----------
        $petData = [
            // owner, name, species, breed, gender, age in years
            ['Mark', 'Max', 'dog', 'Golden Retriever', 'male', 3],
            ['Mark', 'Luna', 'cat', 'Siamese', 'female', 1],
            ['John', 'Buddy', 'dog', 'Labrador Retriever', 'male', 2],
            ['Anna', 'Coco', 'cat', 'Persian', 'female', 2],
            ['Maria', 'Bella', 'dog', 'Shih Tzu', 'female', 5],
            ['James', 'Rocky', 'dog', 'Aspin', 'male', 4],
            ['Sarah', 'Milo', 'cat', 'Siamese', 'male', 1],
        ];

        $pets = [];
        foreach ($petData as [$owner, $name, $species, $breed, $gender, $years]) {
            $pets[$name] = $customers[$owner]->pets()->create([
                'name' => $name,
                'species' => $species,
                'breed' => $breed,
                'gender' => $gender,
                'birthdate' => now()->subYears($years)->subMonths(2)->toDateString(),
            ]);
        }

        // ---------- Appointments ----------
        $appointments = [
            // pet, service, days from today, time, status, reason
            ['Max', 'Consultation', 0, '10:00', 'pending', 'General health check-up.'],
            ['Buddy', 'Vaccination', 0, '11:30', 'confirmed', 'Annual vaccination.'],
            ['Coco', 'Grooming', 1, '13:00', 'pending', 'Full grooming.'],
            ['Luna', 'Consultation', 1, '14:30', 'confirmed', 'Not eating well.'],
            ['Milo', 'Vaccination', 2, '09:30', 'pending', 'First vaccine.'],
            ['Max', 'Vaccination', -30, '10:00', 'completed', 'Rabies booster.'],
            ['Rocky', 'Grooming', -5, '15:00', 'cancelled', 'Owner rescheduled.'],
        ];

        foreach ($appointments as $i => [$petName, $serviceName, $days, $time, $status, $reason]) {
            $pet = $pets[$petName];
            $appointment = new Appointment([
                'customer_id' => $pet->customer_id,
                'pet_id' => $pet->id,
                'service_id' => $service($serviceName),
                'veterinarian_id' => $vet->id,
                'appointment_date' => now()->addDays($days)->toDateString(),
                'appointment_time' => $time,
                'reason' => $reason,
            ]);
            // reference, status and creator are set by trusted code only
            $appointment->reference = 'APP-' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT);
            $appointment->status = $status;
            $appointment->created_by = $staff->id;
            if ($status === 'cancelled') {
                $appointment->cancel_reason = 'Owner asked to reschedule.';
            }
            $appointment->save();
        }

        // ---------- One past medical record for Max (vaccination + released care instructions) ----------
        $record = new MedicalRecord([
            'pet_id' => $pets['Max']->id,
            'record_type' => 'vaccination',
            'record_date' => now()->subDays(30)->toDateString(),
            'weight_kg' => 28.5,
            'temperature_c' => 38.6,
            'chief_complaint' => 'Due for rabies booster.',
            'findings' => 'Healthy, normal vital signs.',
            'diagnosis' => 'Healthy - for vaccination.',
            'notes' => 'No reaction observed after 15 minutes.',
        ]);
        $record->veterinarian_id = $vet->id;
        $record->save();

        $vaccination = new Vaccination([
            'pet_id' => $pets['Max']->id,
            'medical_record_id' => $record->id,
            'vaccine_name' => 'Rabies Vaccine',
            'batch_number' => 'VAC-RAB-B0',
            'date_given' => now()->subDays(30)->toDateString(),
            'next_due_date' => now()->addDays(335)->toDateString(),
        ]);
        $vaccination->given_by = $vet->id;
        $vaccination->save();

        $care = new CareInstruction([
            'medical_record_id' => $record->id,
            'pet_id' => $pets['Max']->id,
            'title' => 'After vaccination care',
            'instructions' => "Let Max rest for 24 hours. Mild sleepiness is normal.\n"
                . "Do not bathe for 3 days. Call the clinic if there is swelling, vomiting or difficulty breathing.",
        ]);
        $care->is_released = true;
        $care->released_at = now()->subDays(30);
        $care->released_by = $vet->id;
        $care->save();

        // ---------- Two waivers (Phase 13): one signed and reviewed, one waiting for the owner ----------
        $signed = Waiver::prepare(WaiverTemplate::where('waiver_type', 'health_certificate')->firstOrFail(), $pets['Max'], $staff->id);
        $signed->status = 'reviewed';
        $signed->signer_name = 'Mark Santos';
        $signed->signed_at = now()->subDays(30);
        $signed->signed_via = 'clinic';
        $signed->reviewed_by = $vet->id;
        $signed->reviewed_at = now()->subDays(30);
        $signed->created_at = now()->subDays(30);   // prepared on the same day as the vaccination
        $signed->content_snapshot = str_replace('Date prepared: ' . now()->format('F j, Y'), 'Date prepared: ' . now()->subDays(30)->format('F j, Y'), $signed->content_snapshot);
        $signed->save();

        Waiver::prepare(WaiverTemplate::where('waiver_type', 'operation')->firstOrFail(), $pets['Luna'], $staff->id);
    }
}
