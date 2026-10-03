@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | ' . $record->pet->name . ' Record')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $record->pet->icon }} {{ $record->pet->name }} — {{ ucfirst($record->record_type) }}</h1>
      <p>{{ $record->record_date->format('F j, Y') }} · {{ $record->veterinarian ? 'Dr. ' . $record->veterinarian->full_name : 'Veterinarian not set' }}</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>

  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Consultation</h2>
    </div>
    <table class="admin-table">
      <tbody>
        <tr>
          <th>Owner</th>
          <td><a href="{{ route('admin.customers.show', $record->pet->customer) }}" style="color: #e89427; font-weight: bold; text-decoration: none;">{{ $record->pet->customer?->full_name }}</a> · {{ $record->pet->customer?->contact_number }}</td>
        </tr>
        <tr><th>Weight</th><td>{{ $record->weight_kg ? $record->weight_kg . ' kg' : '—' }}</td></tr>
        <tr><th>Temperature</th><td>{{ $record->temperature_c ? $record->temperature_c . ' °C' : '—' }}</td></tr>
        <tr><th>Chief Complaint</th><td style="white-space: pre-line;">{{ $record->chief_complaint ?: '—' }}</td></tr>
        <tr><th>Findings</th><td style="white-space: pre-line;">{{ $record->findings ?: '—' }}</td></tr>
        <tr><th>Assessment / Diagnosis</th><td style="white-space: pre-line;">{{ $record->diagnosis ?: '—' }}</td></tr>
        <tr><th>Medical Notes</th><td style="white-space: pre-line;">{{ $record->notes ?: '—' }}</td></tr>
      </tbody>
    </table>
  </div>

  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Treatments / Procedures</h2>
    </div>
    <table class="admin-table">
      <thead>
        <tr><th>Procedure</th><th>Details</th></tr>
      </thead>
      <tbody>
        @forelse ($record->treatments as $treatment)
          <tr><td>{{ $treatment->procedure_name }}</td><td>{{ $treatment->description ?: '—' }}</td></tr>
        @empty
          <tr><td colspan="2" style="text-align: center; color: #777;">No treatments.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Prescriptions</h2>
    </div>
    <table class="admin-table">
      <thead>
        <tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th></tr>
      </thead>
      <tbody>
        @forelse ($record->prescriptions as $prescription)
          <tr>
            <td>{{ $prescription->medicine_name }}</td>
            <td>{{ $prescription->dosage }}</td>
            <td>{{ $prescription->frequency }}</td>
            <td>{{ $prescription->duration ?: '—' }}</td>
            <td>{{ $prescription->instructions ?: '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align: center; color: #777;">No prescriptions.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Vaccinations</h2>
    </div>
    <table class="admin-table">
      <thead>
        <tr><th>Vaccine</th><th>Batch</th><th>Date Given</th><th>Next Due</th></tr>
      </thead>
      <tbody>
        @forelse ($record->vaccinations as $vaccination)
          <tr>
            <td>{{ $vaccination->vaccine_name }}</td>
            <td>{{ $vaccination->batch_number ?: '—' }}</td>
            <td>{{ $vaccination->date_given->format('F j, Y') }}</td>
            <td>{{ $vaccination->next_due_date?->format('F j, Y') ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="4" style="text-align: center; color: #777;">No vaccinations.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Care Instructions for the Owner</h2>
    </div>
    @error('care')
      <p style="color: #c0392b;">{{ $message }}</p>
    @enderror
    <table class="admin-table">
      <thead>
        <tr><th>Title</th><th>Instructions</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        @forelse ($record->careInstructions as $care)
          <tr>
            <td>{{ $care->title }}</td>
            <td style="white-space: pre-line;">{{ $care->instructions }}</td>
            <td>
              @if ($care->is_released)
                @include('partials.status-badge', ['status' => 'completed', 'label' => 'Released'])
                <div style="color: #777; font-size: 13px;">{{ $care->released_at->format('M j, Y g:i A') }}</div>
              @else
                @include('partials.status-badge', ['status' => 'pending', 'label' => 'Draft'])
              @endif
            </td>
            <td>
              @if (! $care->is_released)
                @can('records.write')
                  <form method="post" action="{{ route('admin.care.release', $care) }}" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button class="action-view" type="submit">Release</button>
                  </form>
                  <form method="post" action="{{ route('admin.care.destroy', $care) }}" style="display: inline;"
                        onsubmit="return confirm('Delete this draft?');">
                    @csrf
                    @method('DELETE')
                    <button class="action-edit" type="submit">Delete</button>
                  </form>
                @endcan
              @else
                —
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="4" style="text-align: center; color: #777;">No care instructions yet.</td></tr>
        @endforelse
      </tbody>
    </table>

    @can('records.write')
      <form method="post" action="{{ route('admin.records.care.store', $record) }}" style="margin-top: 20px;">
        @csrf
        <div class="form-group">
          <label for="title">New Care Instructions — Title</label>
          <input type="text" id="title" name="title" value="{{ old('title') }}" placeholder="e.g. Home care after vaccination" required>
        </div>
        <div class="form-group">
          <label for="instructions">Instructions</label>
          <textarea id="instructions" name="instructions" rows="4" placeholder="What the owner should do at home" required>{{ old('instructions') }}</textarea>
        </div>
        <div class="form-group">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
            <input type="checkbox" name="release" value="1" style="width: auto;">
            Release now (the owner can see and download it in the portal)
          </label>
        </div>
        <button class="admin-add-btn" type="submit">Save Care Instructions</button>
      </form>
    @endcan
  </div>

  <div class="admin-tools">
    <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.pets.show', $record->pet) }}'">← Back to {{ $record->pet->name }}</button>
    @can('records.write')
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('admin.records.edit', $record) }}'">Edit Record</button>
    @endcan
    <button class="action-view" type="button" onclick="window.print()">Print</button>
  </div>
</main>
@endsection
