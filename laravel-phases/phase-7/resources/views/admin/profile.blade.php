@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Admin Profile')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Admin Profile</h1>
      <p>View and manage your administrator account information.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Account Information</h2>
    </div>
    <table class="admin-table">
      <tbody>
        <tr>
          <th>Full Name</th>
          <td>Dr. {{ $user->full_name }}</td>
        </tr>
        <tr>
          <th>Email</th>
          <td>{{ $user->email }}</td>
        </tr>
        <tr>
          <th>Mobile</th>
          <td>{{ $user->contact_number ?: '—' }}</td>
        </tr>
        <tr>
          <th>Role</th>
          <td>{{ $user->role?->name }}</td>
        </tr>
        <tr>
          <th>Account Status</th>
          <td>
            <span class="user-status {{ $user->isActive() ? 'active' : 'inactive' }}">{{ ucfirst($user->status) }}</span>
          </td>
        </tr>
        <tr>
          <th>Date Registered</th>
          <td>{{ $user->created_at?->format('F j, Y') }}</td>
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
      <h2>Account Settings</h2>
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('admin.profile.edit') }}'">Edit Profile</button>
      <button class="action-edit" type="button" onclick="window.location.href='{{ route('admin.profile.edit') }}#password'">Change Password</button>
    </div>
  </div>
</main>
@endsection
