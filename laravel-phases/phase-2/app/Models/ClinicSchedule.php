<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Opening hours for one day of the week (0 = Sunday ... 6 = Saturday).
 * Used to create appointment time slots.
 */
class ClinicSchedule extends Model
{
    protected $fillable = ['day_of_week', 'is_open', 'opens_at', 'closes_at', 'slot_minutes', 'max_per_slot'];

    protected function casts(): array
    {
        return ['is_open' => 'boolean'];
    }
}
