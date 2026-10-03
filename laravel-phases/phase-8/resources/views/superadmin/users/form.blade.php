{{-- Used for both "Add User" and "Edit User" --}}
@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | ' . ($user->exists ? 'Edit User' : 'Add User'))
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>{{ $user->exists ? 'Edit User' : 'Add User' }}</h1>
      <p>{{ $user->exists ? 'Update the account details. Leave the password empty to keep the current one.' : 'Create an account for a clinic staff member, veterinarian or customer.' }}</p>
    </div>
  </div>
  <form class="superadmin-settings-card" method="post"
        action="{{ $user->exists ? route('superadmin.users.update', $user) : route('superadmin.users.store') }}">
    @csrf
    @if ($user->exists)
      @method('PUT')
    @endif
    <div class="form-group">
      <label for="firstname">First Name</label>
      <input type="text" id="firstname" name="firstname" value="{{ old('firstname', $user->first_name) }}" required>
    </div>
    <div class="form-group">
      <label for="lastname">Last Name</label>
      <input type="text" id="lastname" name="lastname" value="{{ old('lastname', $user->last_name) }}" required>
    </div>
    <div class="form-group">
      <label for="email">Email Address (login name)</label>
      <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
    </div>
    <div class="form-group">
      <label for="mobile">Mobile Number</label>
      <input type="tel" id="mobile" name="mobile" value="{{ old('mobile', $user->contact_number) }}" placeholder="09XXXXXXXXX" required>
    </div>
    <div class="form-group">
      <label for="role_id">Role</label>
      <select id="role_id" name="role_id" required @disabled($user->exists && $user->is(auth()->user()))>
        <option value="">Select role</option>
        @foreach ($roles as $role)
          <option value="{{ $role->id }}" @selected((int) old('role_id', $user->role_id) === $role->id)>{{ $role->name }}</option>
        @endforeach
      </select>
      @if ($user->exists && $user->is(auth()->user()))
        <input type="hidden" name="role_id" value="{{ $user->role_id }}">
        <small style="color: #64748b;">You cannot change your own role.</small>
      @endif
    </div>
    <div class="form-group">
      <label for="password">{{ $user->exists ? 'New Password (optional)' : 'Password' }}</label>
      <input type="password" id="password" name="password" autocomplete="new-password" placeholder="At least 8 characters, letters and numbers" @required(! $user->exists)>
    </div>
    <div class="form-group">
      <label for="password_confirmation">Confirm Password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" @required(! $user->exists)>
    </div>
    <div style="display: flex; gap: 10px;">
      <button type="submit" class="superadmin-add-btn">{{ $user->exists ? 'Save Changes' : 'Create Account' }}</button>
      <button type="button" class="action-edit" style="padding: 12px 18px;" onclick="window.location.href='{{ route('superadmin.users.index') }}'">Cancel</button>
    </div>
  </form>
</main>
@endsection
