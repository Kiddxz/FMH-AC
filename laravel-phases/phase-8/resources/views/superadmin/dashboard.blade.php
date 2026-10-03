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
        <strong>{{ $userCount }}</strong>
      </div>
    </div>
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">📅</div>
      <div>
        <span>Appointments</span>
        <strong>{{ $appointmentCount }}</strong>
      </div>
    </div>
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">💰</div>
      <div>
        <span>Transactions</span>
        <strong>{{ $transactionCount }}</strong>
      </div>
    </div>
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">📦</div>
      <div>
        <span>Inventory Items</span>
        <strong>{{ $itemCount }}</strong>
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
        <a href="{{ route('superadmin.roles.index') }}" class="superadmin-action">
          <div class="superadmin-action-icon">🛡️</div>
          <div>
            <h3>Roles &amp; Permissions</h3>
            <p>Choose what each role is allowed to do.</p>
          </div>
        </a>
        <a href="{{ route('superadmin.activity-logs') }}" class="superadmin-action">
          <div class="superadmin-action-icon">📋</div>
          <div>
            <h3>Activity Logs</h3>
            <p>See who did what and when.</p>
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
        @php
          $icons = ['Authentication' => '🔐', 'User Accounts' => '👤', 'Roles & Permissions' => '🛡️', 'Pets' => '🐾', 'Customers' => '👥', 'Services' => '🩺', 'Profile' => '👤'];
        @endphp
        @forelse ($recentActivity as $log)
          <div class="superadmin-activity">
            <span>{{ $icons[$log->module] ?? '📋' }}</span>
            <div>
              <strong>{{ $log->module }}: {{ ucfirst(str_replace('_', ' ', $log->action)) }}</strong>
              <p>{{ $log->description }}</p>
            </div>
            <small>{{ $log->created_at?->diffForHumans() }}</small>
          </div>
        @empty
          <p style="color: #64748b;">No activity recorded yet.</p>
        @endforelse
        <p style="margin-top: 15px;"><a href="{{ route('superadmin.activity-logs') }}" style="color: #e89427; font-weight: 700; text-decoration: none;">View all activity logs →</a></p>
      </div>
    </div>
  </section>
</main>
@endsection
