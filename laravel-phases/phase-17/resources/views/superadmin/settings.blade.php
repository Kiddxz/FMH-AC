@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | System Settings')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
@php $days = \App\Http\Controllers\SuperAdmin\SettingController::DAYS; @endphp
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>System Settings</h1>
      <p>Maintain the settings of the FMH Animal Clinic system.</p>
    </div>
  </div>
  <form method="post" action="{{ route('superadmin.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="superadmin-settings-card" style="margin-bottom: 25px;">
      <h2 style="margin: 0 0 18px; color: #26364a;">Clinic Information</h2>
      <p style="margin: -10px 0 18px; color: #64748b;">Printed on receipts, waivers and reports.</p>
      <div class="form-group">
        <label for="clinic_name">Clinic Name</label>
        <input type="text" id="clinic_name" name="clinic_name" value="{{ old('clinic_name', $settings['clinic_name']) }}" required maxlength="100">
        @error('clinic_name') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="clinic_address">Clinic Address</label>
        <input type="text" id="clinic_address" name="clinic_address" value="{{ old('clinic_address', $settings['clinic_address']) }}" required maxlength="255">
        @error('clinic_address') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="clinic_contact">Contact Number</label>
        <input type="text" id="clinic_contact" name="clinic_contact" value="{{ old('clinic_contact', $settings['clinic_contact']) }}" required maxlength="30">
        @error('clinic_contact') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="clinic_email">Clinic Email (optional)</label>
        <input type="email" id="clinic_email" name="clinic_email" value="{{ old('clinic_email', $settings['clinic_email']) }}" maxlength="150">
        @error('clinic_email') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="superadmin-settings-card" style="margin-bottom: 25px;">
      <h2 style="margin: 0 0 18px; color: #26364a;">Inventory Alerts</h2>
      <div class="form-group">
        <label for="expiry_alert_days">Warn this many days before an item expires</label>
        <input type="number" id="expiry_alert_days" name="expiry_alert_days" value="{{ old('expiry_alert_days', $settings['expiry_alert_days']) }}" min="1" max="180" required>
        @error('expiry_alert_days') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="superadmin-table-card" style="margin-bottom: 25px; padding: 25px; overflow-x: auto;">
      <h2 style="margin: 0 0 6px; color: #26364a;">Clinic Hours</h2>
      <p style="margin: 0 0 15px; color: #64748b;">Used to make the appointment time slots. Appointments that were already booked are not changed.</p>
      @error('hours') <p style="color: #c0392b; margin-bottom: 10px;">{{ $message }}</p> @enderror
      <table class="superadmin-table">
        <thead>
          <tr><th>Day</th><th>Open</th><th>Opens</th><th>Closes</th><th>Minutes per Slot</th><th>Pets per Slot</th></tr>
        </thead>
        <tbody>
          @foreach ($days as $day => $dayName)
            @php $s = $schedules->get($day); @endphp
            <tr>
              <td><strong>{{ $dayName }}</strong></td>
              <td>
                <input type="hidden" name="hours[{{ $day }}][is_open]" value="0">
                <input type="checkbox" name="hours[{{ $day }}][is_open]" value="1" aria-label="{{ $dayName }} open" @checked(old("hours.$day.is_open", $s?->is_open))>
              </td>
              <td><input type="time" name="hours[{{ $day }}][opens_at]" value="{{ old("hours.$day.opens_at", $s?->opens_at ? substr($s->opens_at, 0, 5) : '08:00') }}" aria-label="{{ $dayName }} opens"></td>
              <td>
                <input type="time" name="hours[{{ $day }}][closes_at]" value="{{ old("hours.$day.closes_at", $s?->closes_at ? substr($s->closes_at, 0, 5) : '17:00') }}" aria-label="{{ $dayName }} closes">
                @error("hours.$day.closes_at") <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
              </td>
              <td>
                <select name="hours[{{ $day }}][slot_minutes]" aria-label="{{ $dayName }} minutes per slot">
                  @foreach (\App\Http\Controllers\SuperAdmin\SettingController::SLOT_MINUTES as $minutes)
                    <option value="{{ $minutes }}" @selected((int) old("hours.$day.slot_minutes", $s?->slot_minutes ?? 30) === $minutes)>{{ $minutes }} minutes</option>
                  @endforeach
                </select>
              </td>
              <td><input type="number" name="hours[{{ $day }}][max_per_slot]" value="{{ old("hours.$day.max_per_slot", $s?->max_per_slot ?? 3) }}" min="1" max="10" style="width: 80px;" aria-label="{{ $dayName }} pets per slot"></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="superadmin-settings-card" style="margin-bottom: 25px;">
      <h2 style="margin: 0 0 18px; color: #26364a;">System Status</h2>
      <div class="form-group">
        <label for="system_status">System Status</label>
        <select id="system_status" name="system_status">
          <option value="active" @selected(old('system_status', $settings['system_status']) === 'active')>Active</option>
          <option value="maintenance" @selected(old('system_status', $settings['system_status']) === 'maintenance')>Maintenance (only Super Admin can use the system)</option>
        </select>
        @error('system_status') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="maintenance_message">Message shown during maintenance</label>
        <input type="text" id="maintenance_message" name="maintenance_message" value="{{ old('maintenance_message', $settings['maintenance_message']) }}" maxlength="255">
        @error('maintenance_message') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      @if ($settings['system_status'] === 'maintenance')
        <p style="color: #d9534f; font-weight: 600;">⚠️ Maintenance mode is ON. Other users see the "Under Maintenance" page.</p>
      @endif
    </div>

    <button type="submit" class="superadmin-add-btn">Save Settings</button>
  </form>
</main>
@endsection
