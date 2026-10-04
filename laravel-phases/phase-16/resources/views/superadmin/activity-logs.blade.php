@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Activity Logs')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>System Activity Logs</h1>
      <p>Every important action is recorded automatically: who, what, when and from which computer. Entries cannot be changed or deleted.</p>
    </div>
  </div>

  <section class="superadmin-stats">
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">📋</div>
      <div>
        <span>Activities Today</span>
        <strong>{{ $stats['today'] }}</strong>
      </div>
    </div>
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">🔐</div>
      <div>
        <span>Logins Today</span>
        <strong>{{ $stats['logins'] }}</strong>
      </div>
    </div>
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">⚠️</div>
      <div>
        <span>Failed Logins (24 hours)</span>
        <strong style="{{ $stats['failed'] > 0 ? 'color: #d9534f;' : '' }}">{{ $stats['failed'] }}</strong>
      </div>
    </div>
    <div class="superadmin-stat-card">
      <div class="superadmin-stat-icon">🗂️</div>
      <div>
        <span>Total Entries</span>
        <strong>{{ $stats['total'] }}</strong>
      </div>
    </div>
  </section>

  <form class="superadmin-tools" method="get" action="{{ route('superadmin.activity-logs') }}" style="flex-wrap: wrap;">
    <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search activity, user or IP...">
    <select name="module" aria-label="Module">
      <option value="">All Modules</option>
      @foreach ($modules as $option)
        <option value="{{ $option }}" @selected(($filters['module'] ?? null) === $option)>{{ $option }}</option>
      @endforeach
    </select>
    <select name="action" aria-label="Action">
      <option value="">All Actions</option>
      @foreach ($actions as $option)
        <option value="{{ $option }}" @selected(($filters['action'] ?? null) === $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
      @endforeach
    </select>
    <select name="user" aria-label="User">
      <option value="">All Users</option>
      @foreach ($users as $option)
        <option value="{{ $option->id }}" @selected((int) ($filters['user'] ?? 0) === $option->id)>{{ $option->full_name }}</option>
      @endforeach
    </select>
    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="From date" title="From">
    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="To date" title="To">
    <button type="submit" class="superadmin-add-btn">Search</button>
    <button type="button" class="action-view" onclick="window.location.href='{{ route('superadmin.activity-logs', array_filter($filters) + ['format' => 'csv']) }}'">⬇ Export CSV</button>
    <button type="button" class="action-view" onclick="window.location.href='{{ route('superadmin.activity-logs', ['action' => 'login_failed']) }}'">⚠️ Failed Logins</button>
  </form>

  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>User</th>
          <th>Module</th>
          <th>Action</th>
          <th>Activity</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($logs as $log)
          @php $warning = in_array($log->action, \App\Models\ActivityLog::WARNING_ACTIONS, true); @endphp
          <tr>
            <td style="white-space: nowrap;">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
            <td>
              {{ $log->user?->full_name ?? 'Guest' }}
              @if ($log->user?->role)<br><small style="color: #94a3b8;">{{ $log->user->role->name }}</small>@endif
            </td>
            <td>{{ $log->module }}</td>
            <td>
              <span class="status {{ $warning ? 'cancelled' : 'active' }}" @if ($warning) style="background: #ffe5e5; color: #d9534f;" @endif>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</span>
            </td>
            <td>{{ $log->description }}</td>
            <td>{{ $log->ip_address ?? '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; color: #94a3b8;">No activity found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.superadmin-pager', ['items' => $logs])
</main>
@endsection
