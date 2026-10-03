{{-- New / edit appointment form for the clinic.
     Use: @include('partials.appointments.form', ['area' => 'staff'])   ($appointment, $pets, $services, $vets) --}}
@php
  $editing = $appointment->exists;
@endphp
<div class="appointment-form-card">
  <form method="post" action="{{ $editing ? route($area . '.appointments.update', $appointment) : route($area . '.appointments.store') }}">
    @csrf
    @if ($editing)
      @method('PUT')
    @endif
    <div class="appointment-form-row">
      <div class="appointment-form-group">
        <label for="pet_id">Pet (Owner)</label>
        @if ($editing)
          <input type="text" id="pet_id" value="{{ $appointment->pet?->name }} — {{ $appointment->customer?->full_name }}" disabled style="background: #f3f3f3;">
        @else
          <select id="pet_id" name="pet_id" required>
            <option value="">Select pet</option>
            @foreach ($pets as $pet)
              <option value="{{ $pet->id }}" @selected((int) old('pet_id', $appointment->pet_id) === $pet->id)>{{ $pet->name }} ({{ ucfirst($pet->species) }}) — {{ $pet->customer?->full_name }}</option>
            @endforeach
          </select>
        @endif
      </div>
      <div class="appointment-form-group">
        <label for="service_id">Service</label>
        <select id="service_id" name="service_id" required>
          <option value="">Select Service</option>
          @foreach ($services as $service)
            <option value="{{ $service->id }}" @selected((int) old('service_id', $appointment->service_id) === $service->id)>{{ $service->name }} — ₱{{ number_format($service->price, 2) }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="appointment-form-row">
      @include('partials.appointments.slot-picker', [
        'date' => old('appointment_date', $appointment->appointment_date?->format('Y-m-d')),
        'time' => old('appointment_time', $appointment->appointment_time),
        'ignore' => $appointment->id,
        'group' => 'appointment-form-group',
      ])
    </div>
    <div class="appointment-form-group">
      <label for="veterinarian_id">Veterinarian</label>
      <select id="veterinarian_id" name="veterinarian_id">
        <option value="">Not assigned yet</option>
        @foreach ($vets as $vet)
          <option value="{{ $vet->id }}" @selected((int) old('veterinarian_id', $appointment->veterinarian_id) === $vet->id)>Dr. {{ $vet->full_name }}</option>
        @endforeach
      </select>
    </div>
    <div class="appointment-form-group">
      <label for="reason">Reason for Visit</label>
      <textarea id="reason" name="reason" rows="3" placeholder="e.g. General check-up, vaccination...">{{ old('reason', $appointment->reason) }}</textarea>
    </div>
    <div class="appointment-form-group">
      <label for="notes">Additional Notes</label>
      <textarea id="notes" name="notes" rows="3" placeholder="Enter additional notes or concerns...">{{ old('notes', $appointment->notes) }}</textarea>
    </div>
    <div class="appointment-form-actions">
      <a href="{{ $editing ? route($area . '.appointments.show', $appointment) : route($area . '.appointments.index') }}" class="appointment-cancel-btn">Cancel</a>
      <button type="submit" class="appointment-save-btn">{{ $editing ? '✏️ Save Changes' : 'Save Appointment' }}</button>
    </div>
  </form>
</div>
