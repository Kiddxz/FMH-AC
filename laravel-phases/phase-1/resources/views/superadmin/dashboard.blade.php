@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Dashboard')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Super Admin Dashboard</h1>
      <p>Monitor the overall FMH Animal Clinic system.</p>
    </div>
    <div class="superadmin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <section class="superadmin-stats">
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">👥</div>
      <div>
        <span>Total Users</span>
        <strong>35</strong>
      </div>
    </div>
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">📅</div>
      <div>
        <span>Appointments</span>
        <strong>86</strong>
      </div>
    </div>
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">💰</div>
      <div>
        <span>Transactions</span>
        <strong>86</strong>
      </div>
    </div>
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">📦</div>
      <div>
        <span>Inventory Items</span>
        <strong>48</strong>
      </div>
    </div>
  </section>
  <section class="superadmin-content">
    <div class="superadmin-panel">
      <div class="superadmin-panel-header">
        <h2>System Overview</h2>
      </div>
      <div class="superadmin-actions">
        <a href="{{ route('superadmin.users.index') }}" class="superadmin-action">
          <div class="superadmin-action-icon">👥</div>
          <div>
            <h3>User Management</h3>
            <p>Manage accounts, roles, and permissions.</p>
          </div>
        </a>
        <a href="{{ route('superadmin.reports') }}" class="superadmin-action">
          <div class="superadmin-action-icon">📊</div>
          <div>
            <h3>Reports</h3>
            <p>View reports and system information.</p>
          </div>
        </a>
        <a href="{{ route('superadmin.transactions') }}" class="superadmin-action">
          <div class="superadmin-action-icon">💰</div>
          <div>
            <h3>Transactions</h3>
            <p>Monitor recorded clinic transactions.</p>
          </div>
        </a>
        <a href="{{ route('superadmin.inventory') }}" class="superadmin-action">
          <div class="superadmin-action-icon">📦</div>
          <div>
            <h3>Inventory</h3>
            <p>Monitor medicines, vaccines, and supplies.</p>
          </div>
        </a>
      </div>
    </div>
    <div class="superadmin-panel">
      <div class="superadmin-panel-header">
        <h2>Recent System Activity</h2>
      </div>
      <div class="superadmin-activity-list">
        <div class="superadmin-activity">
          <span>👤</span>
          <div>
            <strong>User account updated</strong>
            <p>A user account was updated.</p>
          </div>
          <small>Today</small>
        </div>
        <div class="superadmin-activity">
          <span>📅</span>
          <div>
            <strong>Appointment recorded</strong>
            <p>A new appointment was recorded.</p>
          </div>
          <small>Today</small>
        </div>
        <div class="superadmin-activity">
          <span>💰</span>
          <div>
            <strong>Transaction recorded</strong>
            <p>A clinic transaction was recorded.</p>
          </div>
          <small>Yesterday</small>
        </div>
      </div>
    </div>
  </section>
</main>
@endsection
