<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * System settings saved by Super Admin (Phase 17), e.g. clinic_name, system_status.
 * All settings are read with one query per page and kept for the rest of that page.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    // Used when a setting was never saved
    public const DEFAULTS = [
        'clinic_name' => 'FMH Animal Clinic',
        'clinic_address' => 'Las Piñas City',
        'clinic_contact' => '0932-314-5969',
        'clinic_email' => '',
        'system_status' => 'active',          // active or maintenance
        'maintenance_message' => 'The system is under maintenance. Please try again later.',
        'expiry_alert_days' => '30',          // warn this many days before items expire
    ];

    // Usage: Setting::get('clinic_name')
    public static function get(string $key, ?string $default = null): ?string
    {
        $all = request()->attributes->get('settings');
        if ($all === null) {
            try {
                $all = static::query()->pluck('value', 'key')->all();
            } catch (QueryException) {
                $all = [];   // the database is not set up yet (before "php artisan migrate"): use the defaults
            }
            request()->attributes->set('settings', $all);
        }

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    // Usage: Setting::set('clinic_name', 'FMH Animal Clinic')
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        request()->attributes->remove('settings');   // read again on the next get()
    }

    public static function inMaintenance(): bool
    {
        return static::get('system_status') === 'maintenance';
    }
}
