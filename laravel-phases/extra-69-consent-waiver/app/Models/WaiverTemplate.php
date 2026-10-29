<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The text of a waiver / consent form type (operation, refusal of treatment, etc.).
 */
class WaiverTemplate extends Model
{
    public const TYPES = ['operation', 'refusal_of_treatment', 'health_certificate', 'major_procedure', 'other'];

    // Written in the form text where the procedure goes; the staff type the procedure when they prepare the waiver
    public const PROCEDURE = '[procedure]';

    // The clinic's paper "CONSENT" form (the same words)
    public const CONSENT_TEXT = "I the undersigned acting as owner/agent for the above animal/s, hereby authorize the personnel and/or any of the medical staff of the FMH Animal Clinic to perform [procedure] and all other diagnostic procedure as may be deemed necessary in the interest of the above described animal.\n\n"
        . "That if the procedure fails, I shall not hold any member of the said staff responsible for any untoward effects complications or death beyond control from the said operation, treatment, medication and/or examination, boarding, and confinement. I absolve all of the staff and waive all my rights to claim for any damages.\n\n"
        . 'That I shall pay all the bills agreed upon and in the case of the death of the animal, I give them authority to dispose of the same after 24 hours (additional burial fee).';

    // The first forms of the system, replaced by the "Consent" form (turned off, not deleted)
    public const OLD_FORMS = ['Consent for Surgical Operation', 'Refusal of Treatment Waiver', 'Health Certificate Request and Consent', 'Consent for Major Procedure'];

    protected $fillable = ['title', 'waiver_type', 'body', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // true when the staff must type the procedure for this form
    public function needsProcedure(): bool
    {
        return str_contains($this->body, self::PROCEDURE);
    }

    public function waivers(): HasMany
    {
        return $this->hasMany(Waiver::class);
    }
}
