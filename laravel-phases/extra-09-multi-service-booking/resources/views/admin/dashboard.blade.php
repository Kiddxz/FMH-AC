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
  <section class="admin-dashboard-content">
    <div class="admin-panel">
      <div class="admin-panel-header">
        <h2>Recent Appointments</h2>
        <a href="{{ route('admin.appointments.index') }}">View All</a>
      </div>
      <div class="admin-table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Pet Owner</th>
              <th>Pet</th>
              <th>Service</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($recentAppointments as $appointment)
              <tr>
                <td>{{ $appointment->customer?->full_name }}</td>
                <td>{{ $appointment->pet?->name }}</td>
                <td>{{ $appointment->service_names }}</td>
                <td>{{ $appointment->appointment_date->format('F j, Y') }}</td>
                <td>
                  @include('partials.status-badge', ['status' => $appointment->status])
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" style="text-align: center; color: #777;">No appointments yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="admin-panel">
      <div class="admin-panel-header">
        <h2>Quick Actions</h2>
      </div>
      <div class="admin-quick-actions">
        <a href="{{ route('admin.appointments.index') }}" class="admin-action">
          <div class="admin-action-icon">📅</div>
          <div>
            <h3>Manage Appointments</h3>
            <p>View and manage pet appointments.</p>
          </div>
        </a>
        <a href="{{ route('admin.flow.index') }}" class="admin-action">
          <div class="admin-action-icon">🩺</div>
          <div>
            <h3>Patient Flow</h3>
            <p>See who is waiting and ongoing today.</p>
          </div>
        </a>
        <a href="{{ route('admin.pets.index') }}" class="admin-action">
          <div class="admin-action-icon">🐾</div>
          <div>
            <h3>Manage Pets</h3>
            <p>View registered pets and their records.</p>
          </div>
        </a>
        <a href="{{ route('admin.customers.index') }}" class="admin-action">
          <div class="admin-action-icon">👤</div>
          <div>
            <h3>Customers</h3>
            <p>View pet owners and their pets.</p>
          </div>
        </a>
        <a href="{{ route('admin.services.index') }}" class="admin-action">
          <div class="admin-action-icon">🩺</div>
          <div>
            <h3>Manage Services</h3>
            <p>Manage clinic services and information.</p>
          </div>
        </a>
        <a href="{{ route('admin.transactions.index') }}" class="admin-action">
          <div class="admin-action-icon">💰</div>
          <div>
            <h3>Manage Payments</h3>
            <p>View and manage customer payments.</p>
          </div>
        </a>
        <a href="{{ route('admin.inventory.index') }}" class="admin-action">
          <div class="admin-action-icon">📦</div>
          <div>
            <h3>Manage Inventory</h3>
            <p>Monitor clinic supplies and stock levels.</p>
          </div>
        </a>
        <a href="{{ route('admin.reports.index') }}" class="admin-action">
          <div class="admin-action-icon">📊</div>
          <div>
            <h3>View Reports</h3>
            <p>Appointments, patient flow, inventory, records and sales.</p>
          </div>
        </a>
      </div>
    </div>
  </section>
</main>
@endsection
