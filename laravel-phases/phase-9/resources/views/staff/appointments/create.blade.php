@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | New Appointment')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>New Appointment</h1>
      <p>Book an appointment for a registered pet (for example, a phone booking). It is confirmed right away.</p>
    </div>
  </div>
  @include('partials.appointments.form', ['area' => 'staff'])
</main>
@endsection
