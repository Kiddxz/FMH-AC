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

    protected $fillable = ['title', 'waiver_type', 'body', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function waivers(): HasMany
    {
        return $this->hasMany(Waiver::class);
    }
}
