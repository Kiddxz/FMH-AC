<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A consultation / treatment / vaccination / grooming record written by the veterinarian.
 * diagnosis and notes are typed by the vet (no automated diagnosis).
 */
class MedicalRecord extends Model
{
    public const TYPES = ['consultation', 'treatment', 'vaccination', 'grooming'];

    protected $fillable = [
        'pet_id',
        'patient_visit_id',
        'record_type',
        'record_date',
        'weight_kg',
        'temperature_c',
        'chief_complaint',
        'findings',
        'diagnosis',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'weight_kg' => 'decimal:2',
            'temperature_c' => 'decimal:1',
        ];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinarian_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    public function careInstructions(): HasMany
    {
        return $this->hasMany(CareInstruction::class);
    }
}
