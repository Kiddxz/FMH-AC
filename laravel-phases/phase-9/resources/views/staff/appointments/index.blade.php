@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Appointments')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Appointments</h1>
      <p>View and manage scheduled pet appointments.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  @include('partials.appointments.table', ['area' => 'staff'])
</main>
@endsection
