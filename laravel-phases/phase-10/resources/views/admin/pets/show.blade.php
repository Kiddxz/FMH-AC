@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | ' . $pet->name)
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $pet->icon }} {{ $pet->name }}</h1>
      <p>Pet profile, medical records, vaccinations and appointments.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Pet Information</h2>
    </div>
    <table class="admin-table">
      <tbody>
        <tr><th>Species</th><td>{{ ucfirst($pet->species) }}</td></tr>
        <tr><th>Breed</th><td>{{ $pet->breed ?: '—' }}</td></tr>
        <tr><th>Gender</th><td>{{ ucfirst($pet->gender) }}</td></tr>
        <tr><th>Age</th><td>{{ $pet->age_text }}</td></tr>
        <tr><th>Color / Markings</th><td>{{ $pet->color ?: '—' }}</td></tr>
        <tr><th>Status</th><td>@include('partials.status-badge', ['status' => $pet->status])</td></tr>
        <tr><th>Notes</th><td>{{ $pet->notes ?: '—' }}</td></tr>
        <tr>
          <th>Owner</th>
          <td><a href="{{ route('admin.customers.show', $pet->customer) }}" style="color: #e89427; font-weight: bold; text-decoration: none;">{{ $pet->customer?->full_name }}</a> · {{ $pet->customer?->contact_number }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Medical Records</h2>
      @can('records.write')
        <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('admin.records.create', ['pet' => $pet->id]) }}'">+ Add Record</button>
      @endcan
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Type</th>
          <th>Chief Complaint</th>
          <th>Assessment / Diagnosis</th>
          <th>Veterinarian</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pet->medicalRecords as $record)
          <tr>
            <td>{{ $record->record_date->format('F j, Y') }}</td>
            <td>{{ ucfirst($record->record_type) }}</td>
            <td>{{ $record->chief_complaint ?: '—' }}</td>
            <td>{{ $record->diagnosis ?: '—' }}</td>
            <td>{{ $record->veterinarian ? 'Dr. ' . $record->veterinarian->full_name : '—' }}</td>
            <td><button class="action-view" type="button" onclick="window.location.href='{{ route('admin.records.show', $record) }}'">View</button></td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; color: #777;">No medical records yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Vaccination History</h2>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Date Given</th>
          <th>Vaccine</th>
          <th>Batch</th>
          <th>Next Due</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pet->vaccinations as $vaccination)
          <tr>
            <td>{{ $vaccination->date_given->format('F j, Y') }}</td>
            <td>{{ $vaccination->vaccine_name }}</td>
            <td>{{ $vaccination->batch_number ?: '—' }}</td>
            <td>{{ $vaccination->next_due_date?->format('F j, Y') ?? '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" style="text-align: center; color: #777;">No vaccinations recorded yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Appointments</h2>
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
            <td>{{ $appointment->service?->name }}</td>
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
    <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.pets.index') }}'">← Back to Pets</button>
  </div>
</main>
@endsection
