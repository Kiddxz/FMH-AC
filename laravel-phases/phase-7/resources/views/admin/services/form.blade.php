{{-- Used for both "Add New Service" and "Edit Service" --}}
@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | ' . ($service->exists ? 'Edit Service' : 'Add New Service'))
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $service->exists ? 'Edit Service' : 'Add New Service' }}</h1>
      <p>The price here is the official price used for booking and the POS.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-table-card" method="post"
        action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}">
    @csrf
    @if ($service->exists)
      @method('PUT')
    @endif
    <div class="form-group">
      <label for="name">Service Name</label>
      <input type="text" id="name" name="name" value="{{ old('name', $service->name) }}" placeholder="e.g. Consultation" required>
    </div>
    <div class="form-group">
      <label for="purpose">Purpose / Category</label>
      <select id="purpose" name="purpose" required>
        <option value="">Select purpose</option>
        @foreach (\App\Models\Service::PURPOSES as $option)
          <option value="{{ $option }}" @selected(old('purpose', $service->purpose) === $option)>{{ ucfirst($option) }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="3" placeholder="Short description shown to customers">{{ old('description', $service->description) }}</textarea>
    </div>
    <div class="form-group">
      <label for="price">Price (₱)</label>
      <input type="number" id="price" name="price" value="{{ old('price', $service->price) }}" min="0" step="0.01" placeholder="e.g. 500" required>
    </div>
    <div class="form-group">
      <label for="duration_minutes">Duration (minutes)</label>
      <input type="number" id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', $service->duration_minutes) }}" min="5" max="480" required>
    </div>
    <div class="form-group">
      <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" style="width: auto;" @checked(old('is_active', $service->is_active))>
        Active (customers can book it and staff can sell it)
      </label>
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">{{ $service->exists ? 'Save Changes' : 'Add Service' }}</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.services.index') }}'">Cancel</button>
    </div>
  </form>
</main>
@endsection
