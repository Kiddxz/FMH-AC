@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Activity Logs')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>System Activity Logs</h1>
      <p>Monitor important activities performed in the system.</p>
    </div>
  </div>
  <form class="superadmin-tools" method="get" action="{{ route('superadmin.activity-logs') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search activity or user...">
    <select name="module" onchange="this.form.submit()">
      <option value="">All Modules</option>
      @foreach ($modules as $option)
        <option value="{{ $option }}" @selected($module === $option)>{{ $option }}</option>
      @endforeach
    </select>
    <button type="submit" class="superadmin-add-btn">Search</button>
  </form>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>User</th>
          <th>Activity</th>
          <th>Module</th>
          <th>Date</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($logs as $log)
          <tr>
            <td>{{ $log->user?->full_name ?? 'System' }}</td>
            <td>{{ $log->description }}</td>
            <td>{{ $log->module }}</td>
            <td style="white-space: nowrap;">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
            <td>{{ $log->ip_address ?? '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align: center; color: #94a3b8;">No activity recorded yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.superadmin-pager', ['items' => $logs])
</main>
@endsection
