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
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    // Prepare a waiver from a template: the text is copied now, so later template changes do not affect it
    public static function prepare(WaiverTemplate $template, Pet $pet, int $preparedBy): self
    {
        return DB::transaction(function () use ($template, $pet, $preparedBy) {
            $pet->loadMissing('customer');

            $waiver = new self([
                'waiver_template_id' => $template->id,
                'customer_id' => $pet->customer_id,
                'pet_id' => $pet->id,
                'content_snapshot' => self::textFor($template, $pet),
            ]);
            $waiver->reference = 'TMP-' . uniqid();   // replaced below once the id is known
            $waiver->prepared_by = $preparedBy;
            $waiver->save();

            $waiver->reference = sprintf('WVR-%06d', $waiver->id);
            $waiver->save();

            return $waiver;
        });
    }

    // Staff change the form or the pet of a waiver that was NOT signed yet: the text is made again
    public function refill(WaiverTemplate $template, Pet $pet): void
    {
        $pet->loadMissing('customer');
        $this->waiver_template_id = $template->id;
        $this->customer_id = $pet->customer_id;
        $this->pet_id = $pet->id;
        $this->content_snapshot = self::textFor($template, $pet);
        $this->save();
    }

    // The text the owner reads and signs: owner and pet details on top, then the text of the form
    private static function textFor(WaiverTemplate $template, Pet $pet): string
    {
        return implode("\n", [
            strtoupper($template->title),
            '',
            'Owner: ' . $pet->customer->full_name . ' (' . $pet->customer->contact_number . ')',
            'Pet: ' . $pet->name . ' - ' . ucfirst($pet->species) . ($pet->breed ? ', ' . $pet->breed : '') . ', ' . ucfirst($pet->gender) . ', ' . $pet->age_text,
            'Date prepared: ' . now()->format('F j, Y'),
            '',
            $template->body,
        ]);
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
