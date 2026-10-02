@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Edit Pet')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Edit Pet Profile</h1>
      <p>Owner: {{ $pet->customer?->full_name }}</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-table-card" action="{{ route('staff.pets.update', $pet) }}" method="post">
    @csrf
    @method('PUT')
    <div class="admin-panel-header">
      <div>
        <h2>{{ $pet->icon }} {{ $pet->name }}</h2>
        <p>Update the pet's information.</p>
      </div>
    </div>
    @include('customer.pets.form-fields')
    <div class="form-group">
      <label for="status">Record Status</label>
      <select id="status" name="status" required>
        @foreach (['active' => 'Active', 'deceased' => 'Deceased', 'archived' => 'Archived (no longer a patient)'] as $value => $label)
          <option value="{{ $value }}" @selected(old('status', $pet->status) === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">Save Changes</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.pets.show', $pet) }}'">Cancel</button>
    </div>
  </form>
</main>
@endsection
