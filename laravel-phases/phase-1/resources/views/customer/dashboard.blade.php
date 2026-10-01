@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Dashboard')
@section('body_class', 'dashboard-page')
@section('content')
<main class="dashboard-container">
  <div class="welcome-section">
    <h1>Welcome, Mark!</h1>
    <p>Manage your appointments and pet records here.</p>
  </div>
  <section class="quick-actions">
    <a href="{{ route('portal.appointments.create') }}" class="dashboard-card">
      <span class="dashboard-icon">📅</span>
      <h3>Book Appointment</h3>
      <p>Schedule a veterinary appointment for your pet.</p>
    </a>
    <a href="{{ route('portal.pets.index') }}" class="dashboard-card">
      <span class="dashboard-icon">🐶</span>
      <h3>My Pets</h3>
      <p>View and manage your registered pets.</p>
    </a>
    <a href="{{ route('portal.appointments.index') }}" class="dashboard-card">
      <span class="dashboard-icon">📋</span>
      <h3>Appointment History</h3>
      <p>View your previous and upcoming appointments.</p>
    </a>
  </section>
  <section class="dashboard-summary">
    <div class="summary-box">
      <span>🐾</span>
      <div>
        <h3>0</h3>
        <p>Registered Pets</p>
      </div>
    </div>
    <div class="summary-box">
      <span>📅</span>
      <div>
        <h3>0</h3>
        <p>Upcoming Appointments</p>
      </div>
    </div>
    <div class="summary-box">
      <span>📋</span>
      <div>
        <h3>0</h3>
        <p>Completed Appointments</p>
      </div>
    </div>
  </section>
</main>
@endsection
