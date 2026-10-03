@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Walk-in Check-in')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Walk-in Check-in</h1>
      <p>{{ $customer->full_name }} · {{ $customer->contact_number }}</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>

  <form method="post" action="{{ route('staff.walk-ins.customer.store', $customer) }}">
    @csrf
    <div class="admin-table-card">
      <div class="admin-panel-header">
        <h2>Pet</h2>
      </div>
      <div class="form-group">
        <label for="pet_id">Which pet?</label>
        <select id="pet_id" name="pet_id" required>
          <option value="">Select pet</option>
          @foreach ($customer->pets as $pet)
            <option value="{{ $pet->id }}" @selected(old('pet_id') == $pet->id)>{{ $pet->name }} ({{ ucfirst($pet->species) }})</option>
          @endforeach
          <option value="new" @selected(old('pet_id') === 'new')>+ A new pet</option>
        </select>
        @error('pet_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div id="new-pet-fields" style="display: {{ old('pet_id') === 'new' ? 'block' : 'none' }};">
        @include('staff.walk-ins.pet-fields')
      </div>
    </div>

    <div class="admin-table-card">
      <div class="admin-panel-header">
        <h2>Visit</h2>
      </div>
      @include('staff.walk-ins.visit-fields')
    </div>

    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">Add to Board</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.customers.show', $customer) }}'">Cancel</button>
    </div>
  </form>
</main>
<script>
  // Show the new pet fields only when "+ A new pet" is chosen
  document.getElementById('pet_id').addEventListener('change', function () {
    document.getElementById('new-pet-fields').style.display = this.value === 'new' ? 'block' : 'none';
  });
</script>
@endsection
