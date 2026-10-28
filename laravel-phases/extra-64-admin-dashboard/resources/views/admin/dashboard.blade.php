@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Admin Dashboard')
@section('body_class', 'admin-dashboard-page')
@section('header_class', 'admin-dashboard-header')
@section('nav_class', 'admin-dashboard-nav')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-dashboard-container">
  <div class="admin-dashboard-heading">
    <div>
      <h1>Admin Dashboard</h1>
      <p>Welcome back, Dr. {{ auth()->user()->last_name }}!</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <section class="admin-summary">
    <div class="admin-summary-card">
      <div class="admin-summary-icon">📅</div>
      <div>
        <h3>{{ $todayCount }}</h3>
        <p>Today's Appointments</p>
      </div>
    </div>
    <div class="admin-summary-card">
      <div class="admin-summary-icon">🐾</div>
      <div>
        <h3>{{ $petCount }}</h3>
        <p>Registered Pets</p>
      </div>
    </div>
    <div class="admin-summary-card">
      <div class="admin-summary-icon">👤</div>
      <div>
        <h3>{{ $ownerCount }}</h3>
        <p>Pet Owners</p>
      </div>
    </div>
    <div class="admin-summary-card">
      <div class="admin-summary-icon">⏳</div>
      <div>
        <h3>{{ $pendingCount }}</h3>
        <p>Pending Appointments</p>
      </div>
    </div>
  </section>
  @include('partials.clinic-today', ['area' => 'admin'])
  @include('partials.inventory-alerts', ['area' => 'admin', 'limit' => 3])
  <section class="admin-panel">
    <div class="admin-panel-header">
      <div>
        <h2>Upcoming Appointments</h2>
        <p style="margin: 4px 0 0; color: #64748b; font-size: 14px;">Today and the coming days, the nearest first.</p>
      </div>
      <a href="{{ route('admin.appointments.index') }}">View All</a>
    </div>
    <div class="admin-table-container">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Pet</th>
            <th>Pet Owner</th>
            <th>Service</th>
            <th>Vet</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($upcomingAppointments as $appointment)
            <tr>
              <td style="white-space: nowrap;">{{ $appointment->appointment_date->format('M j, Y') }}</td>
              <td style="white-space: nowrap;">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td>
              <td><strong>{{ $appointment->pet?->name }}</strong></td>
              <td>{{ $appointment->customer?->full_name }}</td>
              <td>{{ $appointment->service_names }}</td>
              <td>{{ $appointment->veterinarian ? 'Dr. ' . $appointment->veterinarian->last_name : '—' }}</td>
              <td>@include('partials.status-badge', ['status' => $appointment->status])</td>
              <td style="white-space: nowrap;">
                <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.appointments.show', $appointment) }}'">View</button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="padding: 28px; text-align: center; color: #777;">No upcoming appointments.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</main>
@endsection
