@extends('layouts.staff')

@section('title', 'FMH Animal Clinic | Assistant Logout')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Assistant Panel')

@section('content')
<main class="admin-container">

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <div>

        <h2>
          Logout
        </h2>

        <p>
          Are you sure you want to log out of your assistant account?
        </p>

      </div>

    </div>

    <div class="admin-tools">

      <button
        class="admin-add-btn"
        type="button"
        onclick="window.location.href='{{ route('login') }}'"
      >
        Yes, Logout
      </button>

      <button
        class="action-view"
        type="button"
        onclick="window.location.href='{{ route('staff.dashboard') }}'"
      >
        Cancel
      </button>

    </div>

  </div>

</main>
@endsection
