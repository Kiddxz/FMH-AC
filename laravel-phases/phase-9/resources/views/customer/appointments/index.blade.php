@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Appointment History')
@section('body_class', 'history-page')
@section('footer', '© 2026 FMH Animal Clinic')
@section('content')
<main class="history-container">
  <div class="history-heading" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 15px; flex-wrap: wrap; text-align: left;">
    <div>
      <h1>Appointment History</h1>
      <p>View your previous and upcoming veterinary appointments.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <a href="{{ route('portal.appointments.download') }}" class="cancel-btn">⬇ Download History</a>
      <a href="{{ route('portal.appointments.create') }}" class="primary-btn">📅 Book Appointment</a>
    </div>
  </div>

  @if ($upcoming->isEmpty() && $past->isEmpty())
    <div class="history-empty">
      <div class="history-empty-icon">📋</div>
      <h2>No Appointments Yet</h2>
      <p>Your previous and upcoming appointments will appear here.</p>
    </div>
  @endif

  @if ($upcoming->isNotEmpty())
    <h2 style="margin: 35px 0 0;">Upcoming</h2>
    <div class="history-list">
      @foreach ($upcoming as $appointment)
        @include('customer.appointments.card', ['appointment' => $appointment])
      @endforeach
    </div>
  @endif

  @if ($past->isNotEmpty())
    <h2 style="margin: 45px 0 0;">Past and Cancelled</h2>
    <div class="history-list">
      @foreach ($past as $appointment)
        @include('customer.appointments.card', ['appointment' => $appointment])
      @endforeach
    </div>
  @endif
</main>
@endsection
