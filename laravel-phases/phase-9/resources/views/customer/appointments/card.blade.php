{{-- One appointment card on the Appointment History page --}}
<div class="history-card">
  <div class="history-card-header">
    <div class="history-pet">
      <div class="history-pet-icon">{{ $appointment->pet?->icon }}</div>
      <div>
        <h2>{{ $appointment->pet?->name }}</h2>
        <p>{{ $appointment->pet?->breed }}</p>
      </div>
    </div>
    @include('partials.status-badge', ['status' => $appointment->status])
  </div>
  <div class="history-details">
    <p><strong>Reference:</strong> {{ $appointment->reference }}</p>
    <p><strong>Service:</strong> {{ $appointment->service?->name }}</p>
    <p><strong>Date:</strong> {{ $appointment->appointment_date->format('F j, Y') }}</p>
    <p><strong>Time:</strong> {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</p>
  </div>
  <div class="pet-actions">
    <a href="{{ route('portal.appointments.show', $appointment) }}">View Details →</a>
  </div>
</div>
