@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Appointment History')
@section('body_class', 'history-page')
@section('footer', '© 2026 FMH Animal Clinic')
@section('content')
<main class="history-container">
  <div class="history-heading">
    <h1>Appointment History</h1>
    <p>View your previous and upcoming veterinary appointments.</p>
  </div>
  <div class="history-card">
    <div class="history-card-header">
      <div class="history-pet">
        <div class="history-pet-icon">🐶</div>
        <div>
          <h2>Buddy</h2>
          <p>Golden Retriever</p>
        </div>
      </div>
      <span class="history-status">Upcoming</span>
    </div>
    <div class="history-info">
      <div class="history-info-item">
        <strong>Service</strong>
        <span>Consultation</span>
      </div>
      <div class="history-info-item">
        <strong>Date</strong>
        <span>August 30, 2026</span>
      </div>
      <div class="history-info-item">
        <strong>Time</strong>
        <span>10:00 AM</span>
      </div>
    </div>
  </div>
  <div class="history-empty">
    <div class="history-empty-icon">📋</div>
    <h2>No More Appointments</h2>
    <p>Your previous and upcoming appointments will appear here.</p>
  </div>
</main>
@endsection
