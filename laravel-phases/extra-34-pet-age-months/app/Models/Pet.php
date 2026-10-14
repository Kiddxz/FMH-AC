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

    // Age (years + months) -> an approximate birthdate, so the age shown keeps going up
    public static function birthdateFromAge(int $years, int $months = 0): string
    {
        return now()->subYears($years)->subMonths($months)->toDateString();
    }

    // Whole years from the birthdate (used to fill the "Age" fields when editing)
    public function getAgeYearsAttribute(): ?int
    {
        return $this->birthdate ? (int) $this->birthdate->diffInYears(now()) : null;
    }

    // The months after the whole years (0 - 11), e.g. 2 years and 3 months -> 3
    public function getAgeMonthsAttribute(): ?int
    {
        return $this->birthdate ? (int) $this->birthdate->diffInMonths(now()) % 12 : null;
    }

    // Age text computed from the birthdate, e.g. "3 years old", "2 years 5 months old" or "5 months old"
    public function getAgeTextAttribute(): string
    {
        if (! $this->birthdate) {
            return 'Unknown';
        }

        $years = $this->age_years;
        $months = $this->age_months;
        $parts = [];
        if ($years >= 1) {
            $parts[] = $years . ($years === 1 ? ' year' : ' years');
        }
        if ($months >= 1) {
            $parts[] = $months . ($months === 1 ? ' month' : ' months');
        }

        return $parts ? implode(' ', $parts) . ' old' : 'Below 1 month old';
    }
}
