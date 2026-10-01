<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Post-treatment care instructions (REQ007).
 * The customer can only see / download them after the vet releases them.
 */
class CareInstruction extends Model
{
    protected $fillable = ['medical_record_id', 'pet_id', 'title', 'instructions'];

    protected function casts(): array
    {
        return [
            'is_released' => 'boolean',
            'released_at' => 'datetime',
        ];
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    // Usage: CareInstruction::released()->get()
    public function scopeReleased(Builder $query): Builder
    {
        return $query->where('is_released', true);
    }
}
