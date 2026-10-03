{{-- Appointment details + status buttons for the clinic. Use: @include('partials.appointments.details', ['area' => 'admin']) --}}
<div class="admin-table-card">
  <div class="admin-panel-header">
    <div>
      <h2>Appointment Information</h2>
      <p>Appointment ID: {{ $appointment->reference }}</p>
    </div>
    @include('partials.status-badge', ['status' => $appointment->status])
  </div>
  <table class="admin-table">
    <tbody>
      <tr><th>Appointment ID</th><td>{{ $appointment->reference }}</td></tr>
      <tr><th>Appointment Date</th><td>{{ $appointment->appointment_date->format('l, F j, Y') }}</td></tr>
      <tr><th>Appointment Time</th><td>{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td></tr>
      <tr><th>Service</th><td>{{ $appointment->service?->name }} (₱{{ number_format($appointment->service?->price ?? 0, 2) }})</td></tr>
      <tr><th>Veterinarian</th><td>{{ $appointment->veterinarian ? 'Dr. ' . $appointment->veterinarian->full_name : 'Not assigned' }}</td></tr>
      <tr><th>Pet Name</th><td>{{ $appointment->pet?->icon }} {{ $appointment->pet?->name }}</td></tr>
      <tr><th>Species</th><td>{{ ucfirst($appointment->pet?->species ?? '') }}</td></tr>
      <tr><th>Breed</th><td>{{ $appointment->pet?->breed ?: '—' }}</td></tr>
      <tr><th>Sex</th><td>{{ ucfirst($appointment->pet?->gender ?? '') }}</td></tr>
      <tr><th>Age</th><td>{{ $appointment->pet?->age_text }}</td></tr>
      <tr><th>Owner</th><td>{{ $appointment->customer?->full_name }}</td></tr>
      <tr><th>Email</th><td>{{ $appointment->customer?->email ?: '—' }}</td></tr>
      <tr><th>Mobile</th><td>{{ $appointment->customer?->contact_number }}</td></tr>
      <tr><th>Reason for Visit</th><td>{{ $appointment->reason ?: '—' }}</td></tr>
      <tr><th>Additional Notes</th><td>{{ $appointment->notes ?: '—' }}</td></tr>
      @if ($appointment->status === 'cancelled')
        <tr><th>Cancel Reason</th><td>{{ $appointment->cancel_reason ?: '—' }}</td></tr>
      @endif
      <tr><th>Booked By</th><td>{{ $appointment->creator?->full_name ?? '—' }} on {{ $appointment->created_at?->format('M j, Y g:i A') }}</td></tr>
    </tbody>
  </table>
</div>

@can('appointments.manage')
  @if (count($next) > 0)
    <div class="admin-table-card">
      <div class="admin-panel-header">
        <div>
          <h2>Update Status</h2>
          <p>Pending → Confirmed → Completed. Pending or confirmed appointments can be cancelled.</p>
        </div>
      </div>
      <div class="admin-tools" style="flex-wrap: wrap;">
        @if (in_array('confirmed', $next))
          <form action="{{ route($area . '.appointments.confirm', $appointment) }}" method="post" onsubmit="return confirm('Confirm this appointment?');">
            @csrf
            @method('PATCH')
            <button class="admin-add-btn" type="submit">✓ Confirm Appointment</button>
          </form>
        @endif
        @if (in_array('completed', $next))
          <form action="{{ route($area . '.appointments.complete', $appointment) }}" method="post" onsubmit="return confirm('Mark this appointment as completed?');">
            @csrf
            @method('PATCH')
            <button class="admin-add-btn" type="submit">✔ Mark as Completed</button>
          </form>
        @endif
        <button class="action-edit" type="button" onclick="window.location.href='{{ route($area . '.appointments.edit', $appointment) }}'">✏️ Edit / Reschedule</button>
      </div>
      @if (in_array('cancelled', $next))
        <form action="{{ route($area . '.appointments.cancel', $appointment) }}" method="post" class="admin-tools" style="margin-top: 15px;"
              onsubmit="return confirm('Cancel this appointment?');">
          @csrf
          @method('PATCH')
          <input type="text" name="cancel_reason" placeholder="Reason for cancelling (required)" required maxlength="500">
          <button class="action-delete" type="submit" style="height: 45px;">✕ Cancel Appointment</button>
        </form>
      @endif
    </div>
  @endif
@endcan

<div class="admin-tools">
  <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.appointments.index') }}'">← Back to Appointments</button>
  @if ($area === 'staff')
    <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.pets.show', $appointment->pet_id) }}'">🐾 Pet Record</button>
  @else
    <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.pets.show', $appointment->pet_id) }}'">🐾 Pet Profile</button>
  @endif
</div>
