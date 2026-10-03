@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Appointment Details')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Appointment Details</h1>
      <p>View and manage appointment information.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  @include('partials.appointments.details', ['area' => 'staff'])
</main>
@endsection
