@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Roles & Permissions')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Roles &amp; Permissions</h1>
      <p>Tick what each role is allowed to do, then click "Save" under that role. Changes apply right away.</p>
    </div>
    <button type="button" class="superadmin-add-btn" style="background: #26364a;" onclick="window.location.href='{{ route('superadmin.users.index') }}'">← Back to Users</button>
  </div>
  @foreach ($roles as $role)
    <form class="superadmin-panel" style="margin-bottom: 25px;" method="post" action="{{ route('superadmin.roles.update', $role) }}">
      @csrf
      @method('PUT')
      <div class="superadmin-panel-header" style="display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">
        <div>
          <h2 style="margin: 0 0 5px;">{{ $role->name }}</h2>
          <p style="margin: 0; color: #64748b;">{{ $role->description }} · {{ $role->users_count }} {{ \Illuminate\Support\Str::plural('account', $role->users_count) }}</p>
        </div>
        <button type="submit" class="superadmin-add-btn">Save {{ $role->name }}</button>
      </div>
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px;">
        @foreach ($permissionsByModule as $module => $permissions)
          <div style="border: 1px solid #eee3d6; border-radius: 10px; padding: 14px;">
            <strong style="display: block; margin-bottom: 8px; color: #26364a;">{{ $module }}</strong>
            @foreach ($permissions as $permission)
              @php
                $locked = $role->slug === \App\Models\Role::SUPER_ADMIN && in_array($permission->slug, $protected, true);
              @endphp
              <label style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px; color: #555; font-size: 14px;">
                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                       @checked($role->permissions->contains('id', $permission->id))
                       @disabled($locked)>
                <span>{{ $permission->name }} @if ($locked)<small style="color: #94a3b8;">(always on)</small>@endif</span>
              </label>
            @endforeach
          </div>
        @endforeach
      </div>
    </form>
  @endforeach
</main>
@endsection
