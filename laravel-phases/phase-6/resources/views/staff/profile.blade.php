@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Staff Profile')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>My Profile</h1>
      <p>View and manage your staff account information.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Account Information</h2>
        <p>Your personal and account details.</p>
      </div>
      <span class="status confirmed">{{ ucfirst($user->status) }}</span>
    </div>
    <table class="admin-table">
      <tbody>
        <tr>
          <th>Full Name</th>
          <td>{{ $user->full_name }}</td>
        </tr>
        <tr>
          <th>Email Address</th>
          <td>{{ $user->email }}</td>
        </tr>
        <tr>
          <th>Mobile Number</th>
          <td>{{ $user->contact_number ?: '—' }}</td>
        </tr>
        <tr>
          <th>Role</th>
          <td>{{ $user->role?->name }}</td>
        </tr>
        <tr>
          <th>Last Login</th>
          <td>{{ $user->last_login_at?->format('F j, Y g:i A') ?? '—' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Account Actions</h2>
        <p>Manage your account settings.</p>
      </div>
    </div>
    <div class="admin-tools">
      <button class="action-edit" type="button" onclick="window.location.href='{{ route('staff.profile.edit') }}'">✏️ Edit Profile</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.profile.edit') }}#password'">🔒 Change Password</button>
    </div>
  </div>
  <div class="admin-tools">
    <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.dashboard') }}'">← Back to Dashboard</button>
  </div>
</main>
@endsection
