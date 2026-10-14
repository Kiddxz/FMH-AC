@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | ' . $appointment->reference)
@section('body_class', 'dashboard-page')
@section('content')
<main class="form-page">
  <div class="page-heading pet-heading">
    <div>
      <h1>Appointment {{ $appointment->reference }}</h1>
      <p>Show this page (or a printout) at the clinic.</p>
    </div>
    @include('partials.status-badge', ['status' => $appointment->status])
  </div>
  <section class="pet-form" style="margin-bottom: 25px;">
    <h2>{{ $appointment->pet?->icon }} {{ $appointment->pet?->name }}</h2>
    <div class="history-details" style="border-top: none; padding-top: 0;">
      <p><strong>Service:</strong> {{ $appointment->service_names }} (₱{{ number_format($appointment->total_price, 2) }}, pay at the clinic)</p>
      <p><strong>Date:</strong> {{ $appointment->appointment_date->format('l, F j, Y') }}</p>
      <p><strong>Time:</strong> {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</p>
      <p><strong>Veterinarian:</strong> {{ $appointment->veterinarian ? 'Dr. ' . $appointment->veterinarian->full_name : 'To be assigned by the clinic' }}</p>
      <p><strong>Pet Owner:</strong> {{ $appointment->customer?->full_name }} · {{ $appointment->customer?->contact_number }}</p>
      <p><strong>Reason / Notes:</strong> {{ $appointment->reason ?: '—' }}</p>
      @if ($appointment->status === 'cancelled')
        <p><strong>Cancel Reason:</strong> {{ $appointment->cancel_reason ?: '—' }}</p>
      @endif
    </div>
    <div class="form-buttons">
      <a href="{{ route('portal.appointments.index') }}" class="cancel-btn">Back to History</a>
      <button type="button" class="primary-btn" onclick="window.print()">🖨 Print</button>
      @can('cancel', $appointment)
        <button type="button" class="primary-btn" data-open-cancel>✕ Cancel Appointment</button>
      @endcan
    </div>
  </section>

  {{-- The reasons box opens only after "Cancel Appointment" is clicked --}}
  @can('cancel', $appointment)
    @include('partials.cancel-reason', ['action' => route('portal.appointments.cancel', $appointment), 'method' => 'PATCH', 'reference' => $appointment->reference])
  @endcan
</main>
@endsection
