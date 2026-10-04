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
      <div class="superadmin-panel-header activity-panel-header">
        <div>
          <h2>Recent System Activity</h2>
          <p>The latest actions made in the system. Red items need attention.</p>
        </div>
        <a href="{{ route('superadmin.activity-logs') }}" class="activity-view-all">View all activity logs →</a>
      </div>
      @php
        $icons = ['Authentication' => '🔐', 'User Accounts' => '👤', 'Roles & Permissions' => '🛡️', 'Pets' => '🐾', 'Customers' => '👥', 'Services' => '🩺', 'Profile' => '👤',
          'Appointments' => '📅', 'Patient Flow' => '🩺', 'Pet Records' => '📋', 'Inventory' => '📦', 'Suppliers' => '🚚', 'Waivers' => '📝',
          'POS' => '💰', 'Reports' => '📊', 'Activity Logs' => '🗂️', 'Backups' => '💾', 'Settings' => '⚙️'];
        // Color of each entry: red = needs attention, green = something was added or finished,
        // blue = sign-in / account, orange = everything else (changes)
        $tones = [
          'red' => array_merge(\App\Models\ActivityLog::WARNING_ACTIONS, ['cancelled', 'expired', 'maintenance_on']),
          'green' => ['created', 'registered', 'restored', 'stock_in', 'payment', 'signed', 'released', 'reviewed', 'checked_in', 'uploaded', 'maintenance_off'],
          'blue' => ['login', 'logout', 'password_changed', 'downloaded', 'exported'],
        ];
        $toneOf = fn ($action) => collect($tones)->search(fn ($actions) => in_array($action, $actions)) ?: 'orange';
      @endphp
      @if ($recentActivity->isEmpty())
        <div class="activity-empty">
          <span>🗂️</span>
          <p>No activity recorded yet.</p>
        </div>
      @else
        <ul class="activity-timeline">
          @foreach ($recentActivity as $log)
            <li class="activity-item tone-{{ $toneOf($log->action) }}">
              <span class="activity-icon">{{ $icons[$log->module] ?? '📋' }}</span>
              <div class="activity-body">
                <div class="activity-title">
                  <span class="activity-chip">{{ $log->module }}</span>
                  <strong>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</strong>
                </div>
                <p>{{ $log->description }}</p>
                <div class="activity-meta">
                  <span>👤 {{ $log->user?->full_name ?? 'System / guest' }}</span>
                  <span title="{{ $log->created_at?->format('F j, Y g:i:s A') }}">🕒 {{ $log->created_at?->diffForHumans() }}</span>
                </div>
              </div>
              <time class="activity-time" datetime="{{ $log->created_at?->toIso8601String() }}">
                {{ $log->created_at?->format('g:i A') }}<small>{{ $log->created_at?->format('M j') }}</small>
              </time>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </section>
</main>
@endsection
