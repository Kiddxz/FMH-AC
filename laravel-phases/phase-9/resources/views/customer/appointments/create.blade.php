@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Appointment')
@section('body_class', 'dashboard-page')
@section('content')
<main class="form-page">
  <div class="page-heading">
    <h1>Book an Appointment</h1>
    <p>Schedule a veterinary appointment for your pet.</p>
  </div>
  @if ($pets->isEmpty())
    <div class="no-history">
      <div class="no-history-icon">🐾</div>
      <h2>Add Your Pet First</h2>
      <p>You need a registered pet before booking. It only takes a minute.</p>
      <p style="margin-top: 20px;"><a href="{{ route('portal.pets.create') }}" class="primary-btn" style="text-decoration: none;">+ Add New Pet</a></p>
    </div>
  @else
    <form class="appointment-form" action="{{ route('portal.appointments.store') }}" method="post">
      @csrf
      <h2>Pet Information</h2>
      <div class="form-group">
        <label for="pet_id">Choose Your Pet</label>
        <select name="pet_id" id="pet_id" required>
          <option value="">Select a pet</option>
          @foreach ($pets as $pet)
            <option value="{{ $pet->id }}" @selected((int) old('pet_id', $selectedPet) === $pet->id)>{{ $pet->icon }} {{ $pet->name }} ({{ ucfirst($pet->species) }}, {{ $pet->breed ?: 'no breed' }}, {{ $pet->age_text }})</option>
          @endforeach
        </select>
        <small style="display: block; margin-top: 6px; color: #777;">Pet not listed? <a href="{{ route('portal.pets.create') }}" style="color: #e89427; font-weight: bold;">Add a new pet</a> first.</small>
      </div>
      <h2 class="form-section-title">Appointment Information</h2>
      <div class="form-group">
        <label for="service_id">Service</label>
        <select name="service_id" id="service_id" required>
          <option value="">Choose a service</option>
          @foreach ($services as $service)
            <option value="{{ $service->id }}" @selected((int) old('service_id') === $service->id)>{{ $service->name }} — ₱{{ number_format($service->price, 2) }} ({{ $service->duration_minutes }} min)</option>
          @endforeach
        </select>
      </div>
      <div class="form-row">
        @include('partials.appointments.slot-picker', ['date' => old('appointment_date'), 'time' => old('appointment_time')])
      </div>
      <div class="form-group">
        <label for="reason">Reason / Notes</label>
        <textarea name="reason" id="reason" rows="5" placeholder="Tell us about your pet's concern">{{ old('reason') }}</textarea>
      </div>
      <button class="primary-btn" type="submit">📅 Book Appointment</button>
      <p style="margin-top: 15px; color: #777; font-size: 14px; text-align: center;">Your booking will be <strong>Pending</strong> until the clinic confirms it. Payment is made at the clinic.</p>
    </form>
  @endif
</main>
@endsection
