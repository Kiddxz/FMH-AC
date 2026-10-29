<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A waiver / consent form prepared for a customer and pet (capstone FR-REQ022 - FR-REQ024).
 * content_snapshot keeps the exact text that was signed: once signed it never changes.
 * status: pending (waiting for the owner) -> signed -> reviewed (by the vet)
 */
class Waiver extends Model
{
    public const STATUSES = ['pending', 'signed', 'reviewed'];

    protected $fillable = [
        'waiver_template_id',
        'customer_id',
        'pet_id',
        'appointment_id',
        'patient_visit_id',
        'content_snapshot',
        'procedure',
        'details',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'signed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    // Prepare a waiver from a template: the text and the details are copied now, so later changes do not affect it.
    // $extra: procedure, color, weight (kg) and temperature (°C) typed by the staff
    public static function prepare(WaiverTemplate $template, Pet $pet, int $preparedBy, array $extra = []): self
    {
        return DB::transaction(function () use ($template, $pet, $preparedBy, $extra) {
            $waiver = new self(['waiver_template_id' => $template->id]);
            $waiver->fillFrom($template, $pet, $extra);
            $waiver->reference = 'TMP-' . uniqid();   // replaced below once the id is known
            $waiver->prepared_by = $preparedBy;
            $waiver->save();

            $waiver->reference = sprintf('WVR-%06d', $waiver->id);
            $waiver->save();

            return $waiver;
        });
    }

    // Staff change a waiver that was NOT signed yet: the text and the details are made again
    public function refill(WaiverTemplate $template, Pet $pet, array $extra = []): void
    {
        $this->fillFrom($template, $pet, $extra);
        $this->save();
    }

    private function fillFrom(WaiverTemplate $template, Pet $pet, array $extra): void
    {
        $pet->loadMissing('customer');
        $procedure = trim((string) ($extra['procedure'] ?? '')) ?: null;
        $weight = $extra['weight'] ?? null;
        $temperature = $extra['temperature'] ?? null;

        $this->waiver_template_id = $template->id;
        $this->customer_id = $pet->customer_id;
        $this->pet_id = $pet->id;
        $this->procedure = $procedure;
        // the text the owner reads and signs, with the procedure written in the blank
        $this->content_snapshot = str_replace(WaiverTemplate::PROCEDURE, $procedure ?? '____________________', $template->body);
        // the details on top of the paper form
        $this->details = [
            'date' => now()->format('F j, Y'),
            'owner' => $pet->customer->full_name,
            'phone' => $pet->customer->contact_number,
            'address' => $pet->customer->address,
            'pet' => $pet->name,
            'species_breed' => ucfirst($pet->species) . ($pet->breed ? ' / ' . $pet->breed : ''),
            'age_sex' => self::shortAge($pet) . ' / ' . ucfirst($pet->gender),
            'color' => trim((string) ($extra['color'] ?? $pet->color)) ?: null,
            'weight' => $weight !== null && $weight !== '' ? rtrim(rtrim(number_format((float) $weight, 2, '.', ''), '0'), '.') . ' kg' : null,
            'temperature' => $temperature !== null && $temperature !== '' ? rtrim(rtrim(number_format((float) $temperature, 1, '.', ''), '0'), '.') . ' °C' : null,
        ];
    }

    // Short age for the small "Age/Sex" blank: "5 years", "7 months" or "Below 1 month"
    private static function shortAge(Pet $pet): string
    {
        if (! $pet->birthdate) {
            return 'Unknown';
        }
        if ($pet->age_years >= 1) {
            return $pet->age_years . ($pet->age_years === 1 ? ' year' : ' years');
        }

        return $pet->age_months >= 1 ? $pet->age_months . ($pet->age_months === 1 ? ' month' : ' months') : 'Below 1 month';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WaiverTemplate::class, 'waiver_template_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
