<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A clinic service with one official price, e.g. Consultation P500.
 * purpose: consultation, vaccination, treatment, grooming (capstone paper).
 */
class Service extends Model
{
    public const PURPOSES = ['consultation', 'vaccination', 'treatment', 'grooming'];

    protected $fillable = ['name', 'purpose', 'description', 'price', 'duration_minutes', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
