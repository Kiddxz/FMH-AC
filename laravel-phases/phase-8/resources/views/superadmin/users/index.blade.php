@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Users')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>User Management</h1>
      <p>Manage user accounts, roles, and permissions.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <button type="button" class="superadmin-add-btn" style="background: #26364a;" onclick="window.location.href='{{ route('superadmin.roles.index') }}'">🛡️ Roles &amp; Permissions</button>
      <button type="button" class="superadmin-add-btn" onclick="window.location.href='{{ route('superadmin.users.create') }}'">+ Add User</button>
    </div>
  </div>
  <form class="superadmin-tools" method="get" action="{{ route('superadmin.users.index') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search user...">
    <select name="role" onchange="this.form.submit()">
      <option value="">All Roles</option>
      @foreach ($roles as $option)
        <option value="{{ $option->slug }}" @selected($role === $option->slug)>{{ $option->name }}</option>
      @endforeach
    </select>
    <select name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      <option value="active" @selected($status === 'active')>Active</option>
      <option value="inactive" @selected($status === 'inactive')>Inactive</option>
    </select>
    <button type="submit" class="superadmin-add-btn">Search</button>
  </form>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Last Login</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($users as $user)
          <tr>
            <td>{{ $user->full_name }} @if ($user->is(auth()->user())) <small style="color: #94a3b8;">(you)</small> @endif</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->role?->name }}</td>
            <td>{{ $user->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</td>
            <td>
              <span class="status {{ $user->isActive() ? 'active' : 'inactive' }}">{{ ucfirst($user->status) }}</span>
            </td>
            <td style="white-space: nowrap;">
              <button type="button" class="action-edit" onclick="window.location.href='{{ route('superadmin.users.edit', $user) }}'">Edit</button>
              @unless ($user->is(auth()->user()))
                <form action="{{ route('superadmin.users.status', $user) }}" method="post" style="display: inline;"
                      onsubmit="return confirm('{{ $user->isActive() ? 'Deactivate' : 'Activate' }} the account of {{ addslashes($user->full_name) }}?');">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="action-delete" @if (! $user->isActive()) style="background: #e7f6ec; color: #287a43;" @endif>{{ $user->isActive() ? 'Deactivate' : 'Activate' }}</button>
                </form>
              @endunless
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; color: #94a3b8;">No users found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.superadmin-pager', ['items' => $users])
</main>
@endsection
