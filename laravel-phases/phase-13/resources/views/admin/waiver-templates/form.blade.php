@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | ' . ($template->exists ? 'Edit Form' : 'Add Form'))
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $template->exists ? 'Edit Waiver Form' : 'Add Waiver Form' }}</h1>
      <p>The owner's name, pet details and date are added on top automatically when a waiver is prepared.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-table-card" method="post" action="{{ $template->exists ? route('admin.waiver-templates.update', $template) : route('admin.waiver-templates.store') }}">
    @csrf
    @if ($template->exists)
      @method('PUT')
    @endif
    <div class="form-group">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" value="{{ old('title', $template->title) }}" required>
      @error('title') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
      <label for="waiver_type">Type</label>
      <select id="waiver_type" name="waiver_type" required>
        @foreach (\App\Models\WaiverTemplate::TYPES as $type)
          <option value="{{ $type }}" @selected(old('waiver_type', $template->waiver_type) === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
        @endforeach
      </select>
      @error('waiver_type') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
      <label for="body">Form Text</label>
      <textarea id="body" name="body" rows="10" required>{{ old('body', $template->body) }}</textarea>
      @error('body') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
      <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" style="width: auto;" @checked(old('is_active', $template->is_active))>
        Active (staff can use this form)
      </label>
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">{{ $template->exists ? 'Save Changes' : 'Add Form' }}</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.waiver-templates.index') }}'">Cancel</button>
    </div>
  </form>
</main>
@endsection
