@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Pet Records')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Pet Record</h1>
      <p>View the pet's information, vaccinations and appointment history.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Pet Information</h2>
        <p>Complete information about the registered pet.</p>
      </div>
      @can('update', $pet)
        <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.pets.edit', $pet) }}'">✏️ Edit Pet</button>
      @endcan
    </div>
    <table class="admin-table">
      <tbody>
        <tr>
          <th>Pet Name</th>
          <td>{{ $pet->icon }} {{ $pet->name }}</td>
        </tr>
        <tr>
          <th>Species</th>
          <td>{{ ucfirst($pet->species) }}</td>
        </tr>
        <tr>
          <th>Breed</th>
          <td>{{ $pet->breed ?: '—' }}</td>
        </tr>
        <tr>
          <th>Sex</th>
          <td>{{ ucfirst($pet->gender) }}</td>
        </tr>
        <tr>
          <th>Age</th>
          <td>{{ $pet->age_text }}</td>
        </tr>
        <tr>
          <th>Color / Markings</th>
          <td>{{ $pet->color ?: '—' }}</td>
        </tr>
        <tr>
          <th>Status</th>
          <td>@include('partials.status-badge', ['status' => $pet->status])</td>
        </tr>
        <tr>
          <th>Notes</th>
          <td>{{ $pet->notes ?: '—' }}</td>
        </tr>
        <tr>
          <th>Owner</th>
          <td>
            @can('customers.view')
              <a href="{{ route('staff.customers.show', $pet->customer) }}" style="color: #e89427; font-weight: bold; text-decoration: none;">{{ $pet->customer?->full_name }}</a>
            @else
              {{ $pet->customer?->full_name }}
            @endcan
          </td>
        </tr>
        <tr>
          <th>Owner Email</th>
          <td>{{ $pet->customer?->email ?: '—' }}</td>
        </tr>
        <tr>
          <th>Owner Mobile</th>
          <td>{{ $pet->customer?->contact_number }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Vaccination History</h2>
        <p>Vaccines given at the clinic. Full medical records are kept by the veterinarian.</p>
      </div>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Date Given</th>
          <th>Vaccine</th>
          <th>Next Due</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pet->vaccinations as $vaccination)
          <tr>
            <td>{{ $vaccination->date_given->format('F j, Y') }}</td>
            <td>{{ $vaccination->vaccine_name }}</td>
            <td>{{ $vaccination->next_due_date?->format('F j, Y') ?? '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="3" style="text-align: center; color: #777;">No vaccinations recorded yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Appointment History</h2>
        <p>Previous and upcoming appointments of this pet.</p>
      </div>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Service</th>
          <th>Veterinarian</th>
          <th>Reason</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pet->appointments as $appointment)
          <tr>
            <td>{{ $appointment->appointment_date->format('F j, Y') }}</td>
            <td>{{ $appointment->service_names }}</td>
            <td>{{ $appointment->veterinarian ? 'Dr. ' . $appointment->veterinarian->full_name : '—' }}</td>
            <td>{{ $appointment->reason ?: '—' }}</td>
            <td>@include('partials.status-badge', ['status' => $appointment->status])</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align: center; color: #777;">No appointments yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="admin-tools">
    <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.pets.index') }}'">← Back to Pets</button>
    <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.appointments.index') }}'">📅 View Appointments</button>
  </div>
</main>
@endsection
