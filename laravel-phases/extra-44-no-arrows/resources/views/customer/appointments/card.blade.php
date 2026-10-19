{{-- One appointment on the Appointment History page: its own box in one row, "View Details" on the right --}}
<div class="appt-row">
  <div class="appt-pet">
    <span class="appt-pet-icon">{{ $appointment->pet?->icon }}</span>
    <span>
      <strong>{{ $appointment->pet?->name }}</strong>
      <small>{{ $appointment->pet?->breed ?: ucfirst((string) $appointment->pet?->species) }}</small>
    </span>
  </div>
  <div class="appt-cell" data-label="Reference">{{ $appointment->reference }}</div>
  <div class="appt-cell appt-services" data-label="Service">{{ $appointment->service_names }}</div>
  <div class="appt-cell" data-label="Date">{{ $appointment->appointment_date->format('M j, Y') }}</div>
  <div class="appt-cell" data-label="Time">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</div>
  <div class="appt-cell" data-label="Status">@include('partials.status-badge', ['status' => $appointment->status])</div>
  <div class="appt-go">
    <a href="{{ route('portal.appointments.show', $appointment) }}">View Details</a>
  </div>
</div>
