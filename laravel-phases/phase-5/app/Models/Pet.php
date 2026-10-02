<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pet extends Model
{
    use SoftDeletes;

    public const SPECIES = ['dog', 'cat', 'bird', 'rabbit', 'hamster', 'reptile', 'other'];

    protected $fillable = [
        'name',
        'species',
        'breed',
        'gender',
        'birthdate',
        'color',
        'notes',
        'status',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return ['birthdate' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(PatientVisit::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    public function careInstructions(): HasMany
    {
        return $this->hasMany(CareInstruction::class);
    }

    // Emoji shown on the pet cards
    public function getIconAttribute(): string
    {
        return match ($this->species) {
            'dog' => '🐶',
            'cat' => '🐱',
            'bird' => '🐦',
            'rabbit' => '🐰',
            'hamster' => '🐹',
            'reptile' => '🦎',
            default => '🐾',
        };
    }

    // Whole years from the birthdate (used to fill the "Age" field when editing)
    public function getAgeYearsAttribute(): ?int
    {
        return $this->birthdate ? (int) $this->birthdate->diffInYears(now()) : null;
    }

    // Age text computed from the birthdate, e.g. "3 years old" or "5 months old"
    public function getAgeTextAttribute(): string
    {
        if (! $this->birthdate) {
            return 'Unknown';
        }

        $years = (int) $this->birthdate->diffInYears(now());
        if ($years >= 1) {
            return $years . ($years === 1 ? ' year old' : ' years old');
        }

        $months = (int) $this->birthdate->diffInMonths(now());
        return $months . ($months === 1 ? ' month old' : ' months old');
    }
}
