@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Staff Logout')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Logout</h2>
        <p>Are you sure you want to log out of your staff account?</p>
      </div>
    </div>
    <form class="admin-tools" action="{{ route('logout') }}" method="post">
      @csrf
      <button class="admin-add-btn" type="submit">Yes, Logout</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.dashboard') }}'">Cancel</button>
    </form>
  </div>
</main>
@endsection
