{{-- Staff prepare a waiver for a pet, or edit one that was not signed yet ($waiver is given when editing) --}}
@php $editing = isset($waiver); @endphp
@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | ' . ($editing ? 'Edit Waiver' : 'Prepare Waiver'))
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $editing ? 'Edit Waiver ' . $waiver->reference : 'Prepare Waiver' }}</h1>
      <p>{{ $editing ? 'Change the form or the pet. This is only possible while the waiver is not signed yet.' : 'Choose the form and the pet. The owner can then sign it here at the clinic or in the customer portal.' }}</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-table-card" method="post" action="{{ $editing ? route('staff.waivers.update', $waiver) : route('staff.waivers.store') }}">
    @csrf
    @if ($editing)
      @method('PUT')
    @endif
    <div class="form-group">
      <label for="waiver_template_id">Form</label>
      <select id="waiver_template_id" name="waiver_template_id" required>
        <option value="">Select form</option>
        @foreach ($templates as $template)
          <option value="{{ $template->id }}" @selected((int) old('waiver_template_id', $editing ? $waiver->waiver_template_id : null) === $template->id)>{{ $template->title }}</option>
        @endforeach
      </select>
      @error('waiver_template_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
      <label for="pet_id">Pet (Owner)</label>
      <select id="pet_id" name="pet_id" required>
        <option value="">Select pet</option>
        @foreach ($pets as $pet)
          <option value="{{ $pet->id }}" @selected((int) old('pet_id', $selectedPet) === $pet->id)>{{ $pet->name }} ({{ ucfirst($pet->species) }}) — {{ $pet->customer?->full_name }}</option>
        @endforeach
      </select>
      @error('pet_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">{{ $editing ? 'Save Changes' : 'Prepare Waiver' }}</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ $editing ? route('staff.waivers.show', $waiver) : route('staff.waivers.index') }}'">Cancel</button>
    </div>
  </form>
</main>
@endsection
