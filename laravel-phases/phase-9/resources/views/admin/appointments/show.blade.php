@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Appointment Details')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
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
  @include('partials.appointments.details', ['area' => 'admin'])
</main>
@endsection
