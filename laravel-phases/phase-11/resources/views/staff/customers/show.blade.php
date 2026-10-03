@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | ' . $customer->full_name)
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $customer->full_name }}</h1>
      <p>Customer information, pets and recent appointments.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Customer Information</h2>
        <p>{{ $customer->user_id ? 'Has a customer portal account.' : 'Walk-in customer (no portal account).' }}</p>
      </div>
      <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        @can('patient_flow.manage')
          <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.walk-ins.customer', $customer) }}'">🩺 Walk-in Check-in</button>
        @endcan
        @can('customers.manage')
          <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.customers.edit', $customer) }}'">✏️ Edit Customer</button>
        @endcan
      </div>
    </div>
    <table class="admin-table">
      <tbody>
        <tr>
          <th>Full Name</th>
          <td>{{ $customer->full_name }}</td>
        </tr>
        <tr>
          <th>Mobile Number</th>
          <td>{{ $customer->contact_number }}</td>
        </tr>
        <tr>
          <th>Email Address</th>
          <td>{{ $customer->email ?: '—' }}</td>
        </tr>
        <tr>
          <th>Address</th>
          <td>{{ $customer->address ?: '—' }}</td>
        </tr>
        <tr>
          <th>Registered</th>
          <td>{{ $customer->created_at?->format('F j, Y') }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Pets</h2>
        <p>Pets owned by this customer.</p>
      </div>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Pet Name</th>
          <th>Species</th>
          <th>Breed</th>
          <th>Age</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($customer->pets as $pet)
          <tr>
            <td>{{ $pet->icon }} {{ $pet->name }}</td>
            <td>{{ ucfirst($pet->species) }}</td>
            <td>{{ $pet->breed ?: '—' }}</td>
            <td>{{ $pet->age_text }}</td>
            <td>
              <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.pets.show', $pet) }}'">View Record</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align: center; color: #777;">No pets registered.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Recent Appointments</h2>
        <p>The 10 most recent appointments.</p>
      </div>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Pet</th>
          <th>Service</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($customer->appointments as $appointment)
          <tr>
            <td>{{ $appointment->appointment_date->format('F j, Y') }}</td>
            <td>{{ $appointment->pet?->name }}</td>
            <td>{{ $appointment->service?->name }}</td>
            <td>@include('partials.status-badge', ['status' => $appointment->status])</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" style="text-align: center; color: #777;">No appointments yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="admin-tools">
    <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.customers.index') }}'">← Back to Customers</button>
  </div>
</main>
@endsection
