@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Assistant Dashboard')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Assistant Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Assistant Dashboard</h1>
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
        <strong>8</strong>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon">⏳</div>
      <div>
        <span>Pending Appointments</span>
        <strong>3</strong>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon">🐾</div>
      <div>
        <span>Registered Pets</span>
        <strong>42</strong>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon">✅</div>
      <div>
        <span>Completed Today</span>
        <strong>5</strong>
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
        <tr>
          <td>Mark Santos</td>
          <td>Max</td>
          <td>Consultation</td>
          <td>10:00 AM</td>
          <td>
            <span class="status pending">Pending</span>
          </td>
          <td>
            <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.appointments.show', 1) }}'">View</button>
          </td>
        </tr>
        <tr>
          <td>John Cruz</td>
          <td>Buddy</td>
          <td>Vaccination</td>
          <td>11:30 AM</td>
          <td>
            <span class="status confirmed">Confirmed</span>
          </td>
          <td>
            <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.appointments.show', 1) }}'">View</button>
          </td>
        </tr>
        <tr>
          <td>Anna Reyes</td>
          <td>Coco</td>
          <td>Grooming</td>
          <td>1:00 PM</td>
          <td>
            <span class="status confirmed">Confirmed</span>
          </td>
          <td>
            <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.appointments.show', 1) }}'">View</button>
          </td>
        </tr>
        <tr>
          <td>Mark Santos</td>
          <td>Luna</td>
          <td>Consultation</td>
          <td>2:30 PM</td>
          <td>
            <span class="status pending">Pending</span>
          </td>
          <td>
            <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.appointments.show', 1) }}'">View</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Quick Access</h2>
        <p>Quickly access common assistant tasks.</p>
      </div>
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.appointments.index') }}'">📅 Manage Appointments</button>
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.pets.index') }}'">🐾 View Pets</button>
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.pets.show', 1) }}'">📋 Pet Records</button>
    </div>
  </div>
</main>
@endsection
