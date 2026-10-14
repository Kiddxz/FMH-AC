@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Appointment History')
@section('body_class', 'history-page')
@section('footer', '© 2026 FMH Animal Clinic')
@section('content')
<style>
  /* One row per appointment, each in its own box (like a table, but with boxes) */
  .appt-list { display: grid; gap: 12px; margin-top: 16px; }
  .appt-head, .appt-row { display: grid; grid-template-columns: 1.4fr 1fr 2fr 1fr 0.8fr 1fr 1.1fr; gap: 14px; align-items: center; }
  .appt-head { padding: 0 22px; color: #8a8f98; font-size: 12.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
  .appt-row { padding: 16px 22px; border: 1px solid #eee6db; border-radius: 14px; background: #fff; box-shadow: 0 3px 10px rgba(38, 54, 74, 0.05); transition: border-color 0.2s, box-shadow 0.2s; }
  .appt-row:hover { border-color: #e89427; box-shadow: 0 8px 20px rgba(232, 148, 39, 0.12); }
  .appt-pet { display: flex; align-items: center; gap: 12px; min-width: 0; }
  .appt-pet-icon { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 44px; height: 44px; border-radius: 12px; background: #fff1df; color: #e89427; font-size: 20px; }
  .appt-pet strong { display: block; color: #26364a; font-size: 16px; }
  .appt-pet small { display: block; color: #8a8f98; font-size: 13px; }
  .appt-cell { min-width: 0; color: #374151; font-size: 14.5px; overflow-wrap: anywhere; }
  .appt-go { text-align: right; }
  .appt-go a { display: inline-block; padding: 9px 14px; border-radius: 10px; background: #fff1df; color: #c46f0c; font-size: 14px; font-weight: 700; text-decoration: none !important; border: none !important; white-space: nowrap; }
  .appt-go a:hover { background: #e89427; color: #fff; }
  /* smaller screens: each box shows its details one under the other */
  @media (max-width: 1000px) {
    .appt-head { display: none; }
    .appt-row { grid-template-columns: 1fr 1fr; gap: 10px 16px; }
    .appt-pet { grid-column: 1 / -1; }
    .appt-services { grid-column: 1 / -1; }
    .appt-cell::before { content: attr(data-label); display: block; margin-bottom: 2px; color: #8a8f98; font-size: 11.5px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
    .appt-go { grid-column: 1 / -1; text-align: left; }
  }
</style>
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

  @foreach (['Upcoming' => $upcoming, 'Past and Cancelled' => $past] as $title => $appointments)
    @if ($appointments->isNotEmpty())
      <h2 style="margin: {{ $loop->first ? '35px' : '45px' }} 0 0;">{{ $title }}</h2>
      <div class="appt-list">
        <div class="appt-head" aria-hidden="true">
          <span>Pet</span><span>Reference</span><span>Service</span><span>Date</span><span>Time</span><span>Status</span><span></span>
        </div>
        @foreach ($appointments as $appointment)
          @include('customer.appointments.card', ['appointment' => $appointment])
        @endforeach
      </div>
    @endif
  @endforeach
</main>
@endsection
