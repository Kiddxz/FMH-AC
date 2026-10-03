@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Edit Appointment')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>✏️ Edit Appointment</h1>
      <p>{{ $appointment->reference }}: change the service, veterinarian, notes, or move it to another date and time.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  @include('partials.appointments.form', ['area' => 'admin'])
</main>
@endsection
