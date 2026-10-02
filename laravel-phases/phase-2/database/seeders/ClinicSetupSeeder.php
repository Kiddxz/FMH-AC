<?php

namespace Database\Seeders;

use App\Models\ClinicSchedule;
use App\Models\Service;
use App\Models\Setting;
use App\Models\WaiverTemplate;
use Illuminate\Database\Seeder;

/**
 * Clinic services (one official price each), opening hours,
 * system settings and the waiver / consent form templates.
 */
class ClinicSetupSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Services: purpose = consultation / vaccination / treatment / grooming ----------
        $services = [
            ['Consultation', 'consultation', 'General veterinary consultation and health assessment.', 500, 30, true],
            ['Vaccination', 'vaccination', 'Vaccination service to help protect pets from common diseases.', 800, 20, true],
            ['General Treatment', 'treatment', 'Treatment of illness or injury as prescribed by the veterinarian.', 700, 45, true],
            ['Deworming', 'treatment', "Deworming treatment to help maintain your pet's health.", 350, 15, true],
            ['Grooming', 'grooming', 'Professional grooming service for dogs and cats.', 600, 60, true],
            ['Nail Trimming', 'grooming', 'Basic nail trimming and paw care for pets.', 250, 15, false],
        ];

        foreach ($services as [$name, $purpose, $description, $price, $minutes, $active]) {
            Service::updateOrCreate(['name' => $name], [
                'purpose' => $purpose,
                'description' => $description,
                'price' => $price,
                'duration_minutes' => $minutes,
                'is_active' => $active,
            ]);
        }

        // ---------- Clinic hours: Monday-Saturday 8:00 AM - 5:00 PM, closed on Sunday ----------
        for ($day = 0; $day <= 6; $day++) {
            $open = $day !== 0; // 0 = Sunday
            ClinicSchedule::updateOrCreate(['day_of_week' => $day], [
                'is_open' => $open,
                'opens_at' => $open ? '08:00:00' : null,
                'closes_at' => $open ? '17:00:00' : null,
                'slot_minutes' => 30,
                'max_per_slot' => 3,
            ]);
        }

        // ---------- System settings (Super Admin can change these in Phase 17) ----------
        $settings = [
            'clinic_name' => 'FMH Animal Clinic',
            'clinic_address' => 'Las Piñas City',
            'clinic_contact' => '0932-314-5969',
            'system_status' => 'active',          // active or maintenance
            'expiry_alert_days' => '30',          // warn this many days before items expire
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }

        // ---------- Waiver / consent templates (from the client interview) ----------
        $templates = [
            ['Consent for Surgical Operation', 'operation',
                "I, the undersigned owner, authorize the veterinarians of FMH Animal Clinic to perform the surgical operation discussed with me on my pet. "
                . "I understand that every operation and anesthesia carries risks, and that the veterinarian has explained the procedure, its risks and alternatives. "
                . "I agree to pay the clinic fees for the procedure and any related care."],
            ['Refusal of Treatment Waiver', 'refusal_of_treatment',
                "I, the undersigned owner, decline the treatment recommended by the veterinarians of FMH Animal Clinic for my pet. "
                . "The possible consequences of refusing treatment have been explained to me. "
                . "I release FMH Animal Clinic and its staff from responsibility for any result of this decision."],
            ['Health Certificate Request and Consent', 'health_certificate',
                "I, the undersigned owner, request a health certificate for my pet and consent to the examination needed to issue it. "
                . "I confirm that the information I gave about my pet is true and complete."],
            ['Consent for Major Procedure', 'major_procedure',
                "I, the undersigned owner, authorize the veterinarians of FMH Animal Clinic to perform the major procedure explained to me on my pet. "
                . "I understand the risks and expected costs, and I agree to follow the post-treatment care instructions."],
        ];

        foreach ($templates as [$title, $type, $body]) {
            WaiverTemplate::updateOrCreate(['title' => $title], [
                'waiver_type' => $type,
                'body' => $body,
                'is_active' => true,
            ]);
        }
    }
}
