<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClinicSchedule;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * System settings for the Super Admin (capstone SCOPE-14):
 *  - clinic information printed on receipts, waivers and reports
 *  - clinic hours (used to make the appointment time slots)
 *  - how many days before expiry an item counts as "expiring soon"
 *  - maintenance mode (only the Super Admin can use the system while it is on)
 */
class SettingController extends Controller
{
    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public const SLOT_MINUTES = [15, 20, 30, 45, 60];

    public function edit(): View
    {
        return view('superadmin.settings', [
            'settings' => collect(Setting::DEFAULTS)->map(fn ($default, $key) => Setting::get($key)),
            'schedules' => ClinicSchedule::orderBy('day_of_week')->get()->keyBy('day_of_week'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'clinic_name' => ['required', 'string', 'max:100'],
            'clinic_address' => ['required', 'string', 'max:255'],
            'clinic_contact' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'clinic_email' => ['nullable', 'email', 'max:150'],
            'expiry_alert_days' => ['required', 'integer', 'min:1', 'max:180'],
            'system_status' => ['required', 'in:active,maintenance'],
            'maintenance_message' => ['nullable', 'string', 'max:255'],
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.is_open' => ['nullable', 'boolean'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.slot_minutes' => ['required', 'integer', 'in:' . implode(',', self::SLOT_MINUTES)],
            'hours.*.max_per_slot' => ['required', 'integer', 'min:1', 'max:10'],
        ], [
            'clinic_contact.regex' => 'The contact number may only have numbers, spaces, +, - and ( ).',
            'hours.*.opens_at.date_format' => 'Use a time like 08:00.',
            'hours.*.closes_at.date_format' => 'Use a time like 17:00.',
        ]);

        // An open day needs an opening time before its closing time
        $errors = [];
        foreach (range(0, 6) as $day) {
            $row = $data['hours'][$day] ?? null;
            if (! $row || empty($row['is_open'])) {
                continue;
            }
            if (empty($row['opens_at']) || empty($row['closes_at']) || $row['opens_at'] >= $row['closes_at']) {
                $errors["hours.{$day}.closes_at"] = self::DAYS[$day] . ': the closing time must be after the opening time.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
        if (! collect($data['hours'])->contains(fn ($row) => ! empty($row['is_open']))) {
            throw ValidationException::withMessages(['hours' => 'The clinic must be open on at least one day.']);
        }

        $changed = [];
        DB::transaction(function () use ($data, &$changed) {
            foreach (array_keys(Setting::DEFAULTS) as $key) {
                $value = (string) ($data[$key] ?? '');
                if (Setting::get($key) !== $value) {
                    $changed[] = str_replace('_', ' ', $key);
                    Setting::set($key, $value);
                }
            }

            foreach (range(0, 6) as $day) {
                $row = $data['hours'][$day];
                $open = ! empty($row['is_open']);
                $schedule = ClinicSchedule::firstOrNew(['day_of_week' => $day]);
                $schedule->fill([
                    'is_open' => $open,
                    'opens_at' => $open ? $row['opens_at'] . ':00' : null,
                    'closes_at' => $open ? $row['closes_at'] . ':00' : null,
                    'slot_minutes' => (int) $row['slot_minutes'],
                    'max_per_slot' => (int) $row['max_per_slot'],
                ]);
                if ($schedule->isDirty()) {
                    $changed[] = self::DAYS[$day] . ' hours';
                    $schedule->save();
                }
            }
        });

        if ($changed) {
            ActivityLog::record('updated', 'Settings', 'Changed settings: ' . implode(', ', $changed) . '.');
        }
        if (in_array('system status', $changed, true)) {
            ActivityLog::record($data['system_status'] === 'maintenance' ? 'maintenance_on' : 'maintenance_off', 'Settings',
                $data['system_status'] === 'maintenance' ? 'Turned ON maintenance mode.' : 'Turned OFF maintenance mode.');
        }

        return back()->with('status', $changed ? 'Settings saved.' : 'Nothing was changed.');
    }
}
