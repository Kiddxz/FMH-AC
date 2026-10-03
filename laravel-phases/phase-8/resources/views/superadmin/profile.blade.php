@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Profile')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>My Profile</h1>
      <p>View and manage your Super Admin account.</p>
    </div>
    <button type="button" class="superadmin-add-btn" onclick="window.location.href='{{ route('superadmin.profile.edit') }}'">Edit Profile / Change Password</button>
  </div>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <tbody>
        <tr><th>Full Name</th><td>{{ $user->full_name }}</td></tr>
        <tr><th>Email</th><td>{{ $user->email }}</td></tr>
        <tr><th>Mobile</th><td>{{ $user->contact_number ?: '—' }}</td></tr>
        <tr><th>Role</th><td>{{ $user->role?->name }}</td></tr>
        <tr><th>Account Status</th><td><span class="status active">{{ ucfirst($user->status) }}</span></td></tr>
        <tr><th>Last Login</th><td>{{ $user->last_login_at?->format('F j, Y g:i A') ?? '—' }}</td></tr>
      </tbody>
    </table>
  </div>
</main>
@endsection
