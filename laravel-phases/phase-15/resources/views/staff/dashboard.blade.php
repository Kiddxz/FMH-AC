@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Staff Dashboard')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Staff Dashboard</h1>
      <p>Manage today's appointments and assist with pet records.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-stats">
    <div class="admin-stat-card">
      <div class="admin-stat-icon">📅</div>
      <div>
        <span>Appointments Today</span>
        <strong>{{ $todayCount }}</strong>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon">⏳</div>
      <div>
        <span>Pending Appointments</span>
        <strong>{{ $pendingCount }}</strong>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon">🐾</div>
      <div>
        <span>Registered Pets</span>
        <strong>{{ $petCount }}</strong>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon">✅</div>
      <div>
        <span>Completed Today</span>
        <strong>{{ $completedTodayCount }}</strong>
      </div>
    </div>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Today's Appointments</h2>
        <p>View and manage today's scheduled appointments.</p>
      </div>
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.appointments.index') }}'">View All Appointments</button>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Pet Owner</th>
          <th>Pet</th>
          <th>Service</th>
          <th>Time</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($todaysAppointments as $appointment)
          <tr>
            <td>{{ $appointment->customer?->full_name }}</td>
            <td>{{ $appointment->pet?->name }}</td>
            <td>{{ $appointment->service?->name }}</td>
            <td>{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td>
            <td>
              @include('partials.status-badge', ['status' => $appointment->status])
            </td>
            <td>
              <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.appointments.show', $appointment) }}'">View</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; color: #777;">No appointments today.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.clinic-today', ['area' => 'staff'])
  @include('partials.inventory-alerts', ['area' => 'staff', 'limit' => 3])
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Quick Access</h2>
        <p>Quickly access common front desk tasks.</p>
      </div>
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.appointments.index') }}'">📅 Manage Appointments</button>
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.pets.index') }}'">🐾 View Pets</button>
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.customers.index') }}'">👥 Customers</button>
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.flow.index') }}'">🩺 Patient Flow</button>
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.inventory.index') }}'">📦 Inventory</button>
      @can('pos.manage')
        <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.pos.create') }}'">💳 POS</button>
      @endcan
      @can('reports.view')
        <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.reports.index') }}'">📊 Reports</button>
      @endcan
    </div>
  </div>
</main>
@endsection
