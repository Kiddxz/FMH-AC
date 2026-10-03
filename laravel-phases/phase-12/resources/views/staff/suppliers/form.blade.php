@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | ' . ($supplier->exists ? 'Edit Supplier' : 'Add Supplier'))
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $supplier->exists ? 'Edit Supplier' : 'Add Supplier' }}</h1>
      <p>Supplier records are kept even when inactive, because deliveries point to them.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-table-card" method="post" action="{{ $supplier->exists ? route('staff.suppliers.update', $supplier) : route('staff.suppliers.store') }}">
    @csrf
    @if ($supplier->exists)
      @method('PUT')
    @endif
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0 15px;">
      <div class="form-group">
        <label for="name">Supplier Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
        @error('name') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="contact_person">Contact Person</label>
        <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}">
        @error('contact_person') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="contact_number">Contact Number</label>
        <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number', $supplier->contact_number) }}" placeholder="09171234567" maxlength="11">
        @error('contact_number') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $supplier->email) }}">
        @error('email') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
    </div>
    <div class="form-group">
      <label for="address">Address</label>
      <input type="text" id="address" name="address" value="{{ old('address', $supplier->address) }}">
      @error('address') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
      <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" style="width: auto;" @checked(old('is_active', $supplier->is_active))>
        Active
      </label>
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">{{ $supplier->exists ? 'Save Changes' : 'Add Supplier' }}</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.suppliers.index') }}'">Cancel</button>
    </div>
  </form>
</main>
@endsection
