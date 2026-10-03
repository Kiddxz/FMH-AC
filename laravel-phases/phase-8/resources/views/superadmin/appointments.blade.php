@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Appointments')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Appointment Lists</h1>
      <p>Monitor clinic appointments and schedules (view only).</p>
    </div>
  </div>
  <form class="superadmin-tools" method="get" action="{{ route('superadmin.appointments') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search appointment...">
    <select name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      @foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $option)
        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
    <button type="submit" class="superadmin-add-btn">Search</button>
  </form>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Customer</th>
          <th>Pet</th>
          <th>Service</th>
          <th>Date</th>
          <th>Time</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($appointments as $appointment)
          <tr>
            <td>{{ $appointment->reference }}</td>
            <td>{{ $appointment->customer?->full_name }}</td>
            <td>{{ $appointment->pet?->name }}</td>
            <td>{{ $appointment->service?->name }}</td>
            <td>{{ $appointment->appointment_date->format('F j, Y') }}</td>
            <td>{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td>
            <td>@include('partials.status-badge', ['status' => $appointment->status])</td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; color: #94a3b8;">No appointments found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.superadmin-pager', ['items' => $appointments])
</main>
@endsection
