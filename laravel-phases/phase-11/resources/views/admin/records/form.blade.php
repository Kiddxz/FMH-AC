{{-- Used for both "Add Pet Record" and "Edit Pet Record" --}}
@extends('layouts.admin')
@php
  $editing = $record->exists;
  $date = old('record_date', $record->record_date?->format('Y-m-d'));
@endphp
@section('title', 'FMH Animal Clinic | ' . ($editing ? 'Edit Pet Record' : 'Add Pet Record'))
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $editing ? 'Edit Pet Record' : 'Add Pet Record' }}</h1>
      <p>The assessment / diagnosis is typed by the veterinarian. Nothing on this page is automatic.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>

  <form method="post" action="{{ $editing ? route('admin.records.update', $record) : route('admin.records.store') }}">
    @csrf
    @if ($editing)
      @method('PUT')
    @elseif ($record->patient_visit_id)
      <input type="hidden" name="patient_visit_id" value="{{ $record->patient_visit_id }}">
    @endif

    <div class="admin-table-card">
      <div class="admin-panel-header">
        <h2>Consultation</h2>
      </div>
      <div class="form-group">
        <label for="pet_id">Pet (Owner)</label>
        @if ($editing)
          <input type="text" id="pet_id" value="{{ $record->pet->name }} — {{ $record->pet->customer?->full_name }}" disabled style="background: #f3f3f3;">
        @else
          <select id="pet_id" name="pet_id" required>
            <option value="">Select pet</option>
            @foreach ($pets as $pet)
              <option value="{{ $pet->id }}" @selected((int) old('pet_id', $record->pet_id) === $pet->id)>{{ $pet->name }} ({{ ucfirst($pet->species) }}) — {{ $pet->customer?->full_name }}</option>
            @endforeach
          </select>
        @endif
      </div>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0 15px;">
        <div class="form-group">
          <label for="record_type">Record Type</label>
          <select id="record_type" name="record_type" required>
            @foreach (\App\Models\MedicalRecord::TYPES as $type)
              <option value="{{ $type }}" @selected(old('record_type', $record->record_type) === $type)>{{ ucfirst($type) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label for="record_date">Date</label>
          <input type="date" id="record_date" name="record_date" value="{{ $date }}" max="{{ today()->format('Y-m-d') }}" required>
        </div>
        <div class="form-group">
          <label for="weight_kg">Weight (kg)</label>
          <input type="number" id="weight_kg" name="weight_kg" value="{{ old('weight_kg', $record->weight_kg) }}" min="0.01" max="200" step="0.01" placeholder="e.g. 12.5">
        </div>
        <div class="form-group">
          <label for="temperature_c">Temperature (°C)</label>
          <input type="number" id="temperature_c" name="temperature_c" value="{{ old('temperature_c', $record->temperature_c) }}" min="30" max="45" step="0.1" placeholder="e.g. 38.5">
        </div>
      </div>
      <div class="form-group">
        <label for="chief_complaint">Chief Complaint</label>
        <textarea id="chief_complaint" name="chief_complaint" rows="2" placeholder="Why the pet was brought in (e.g. vomiting for 2 days)">{{ old('chief_complaint', $record->chief_complaint) }}</textarea>
      </div>
      <div class="form-group">
        <label for="findings">Findings / Physical Exam</label>
        <textarea id="findings" name="findings" rows="3" placeholder="What the vet found during the exam">{{ old('findings', $record->findings) }}</textarea>
      </div>
      <div class="form-group">
        <label for="diagnosis">Assessment / Diagnosis <span style="font-weight: normal; color: #777;">(typed by the vet; required for consultation and treatment)</span></label>
        <textarea id="diagnosis" name="diagnosis" rows="3" placeholder="Veterinarian's assessment">{{ old('diagnosis', $record->diagnosis) }}</textarea>
      </div>
      <div class="form-group">
        <label for="notes">Medical Notes <span style="font-weight: normal; color: #777;">(for the clinic only; not shown to the owner)</span></label>
        <textarea id="notes" name="notes" rows="3" placeholder="Internal notes">{{ old('notes', $record->notes) }}</textarea>
      </div>
    </div>

    @include('admin.records.rows', [
      'list' => 'treatments', 'title' => 'Treatments / Procedures', 'add' => '+ Add Treatment',
      'rows' => old('treatments', $record->treatments->map->only(['procedure_name', 'description'])->all()),
      'fields' => [
        'procedure_name' => ['Procedure', 'text', 'e.g. Wound cleaning'],
        'description' => ['Details', 'text', 'Optional details'],
      ],
    ])

    @include('admin.records.rows', [
      'list' => 'prescriptions', 'title' => 'Prescriptions', 'add' => '+ Add Medicine',
      'rows' => old('prescriptions', $record->prescriptions->map->only(['medicine_name', 'dosage', 'frequency', 'duration', 'instructions'])->all()),
      'fields' => [
        'medicine_name' => ['Medicine', 'text', 'e.g. Amoxicillin 250mg'],
        'dosage' => ['Dosage', 'text', 'e.g. 1 tablet'],
        'frequency' => ['Frequency', 'text', 'e.g. Twice a day'],
        'duration' => ['Duration', 'text', 'e.g. 7 days'],
        'instructions' => ['Instructions', 'text', 'e.g. Give after meals'],
      ],
    ])

    @include('admin.records.rows', [
      'list' => 'vaccinations', 'title' => 'Vaccinations (date given = record date)', 'add' => '+ Add Vaccine',
      'rows' => old('vaccinations', $record->vaccinations->map(fn ($v) => [
          'vaccine_name' => $v->vaccine_name,
          'batch_number' => $v->batch_number,
          'next_due_date' => $v->next_due_date?->format('Y-m-d'),
      ])->all()),
      'fields' => [
        'vaccine_name' => ['Vaccine', 'text', 'e.g. Anti-Rabies'],
        'batch_number' => ['Batch No.', 'text', 'From the vial'],
        'next_due_date' => ['Next Due', 'date', ''],
      ],
    ])

    @unless ($editing)
      <div class="admin-table-card">
        <div class="admin-panel-header">
          <h2>Care Instructions for the Owner (optional)</h2>
        </div>
        <div class="form-group">
          <label for="care_title">Title</label>
          <input type="text" id="care_title" name="care_title" value="{{ old('care_title') }}" placeholder="e.g. Home care after surgery">
        </div>
        <div class="form-group">
          <label for="care_instructions">Instructions</label>
          <textarea id="care_instructions" name="care_instructions" rows="4" placeholder="What the owner should do at home">{{ old('care_instructions') }}</textarea>
        </div>
        <div class="form-group">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
            <input type="checkbox" name="care_release" value="1" style="width: auto;" @checked(old('care_release'))>
            Release now (the owner can see and download it in the portal)
          </label>
        </div>
      </div>
    @endunless

    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">{{ $editing ? 'Save Changes' : 'Save Record' }}</button>
      <button class="action-view" type="button"
              onclick="window.location.href='{{ $editing ? route('admin.records.show', $record) : route('admin.records.index') }}'">Cancel</button>
    </div>
  </form>
</main>

<script>
  // "+ Add" copies the hidden template row; "Remove" deletes a row
  document.querySelectorAll('[data-add-row]').forEach(function (button) {
    button.addEventListener('click', function () {
      var list = button.dataset.addRow;
      var box = document.querySelector('[data-rows="' + list + '"]');
      var template = document.querySelector('[data-template="' + list + '"]');
      var index = Number(box.dataset.next);
      box.dataset.next = index + 1;
      box.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, index));
    });
  });
  document.addEventListener('click', function (event) {
    if (event.target.matches('[data-remove-row]')) {
      event.target.closest('.record-row').remove();
    }
  });
</script>
@endsection
