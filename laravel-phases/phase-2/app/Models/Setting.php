<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * System settings saved by Super Admin, e.g. clinic_name, maintenance_mode.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    // Usage: Setting::get('clinic_name', 'FMH Animal Clinic')
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    // Usage: Setting::set('clinic_name', 'FMH Animal Clinic')
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
