@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | New Walk-in')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>New Walk-in</h1>
      <p>Register a customer and pet who came without an appointment. The pet goes straight to the patient flow board.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>

  @if (session('duplicates'))
    @php $matches = \App\Models\Customer::with('pets')->whereIn('id', session('duplicates'))->get(); @endphp
    <div class="admin-table-card" style="border: 2px solid #e89427;">
      <div class="admin-panel-header">
        <h2>⚠️ This mobile number is already registered</h2>
      </div>
      <p style="margin-bottom: 12px;">Is the customer one of these? If yes, check them in from their record so their pets and history stay together.</p>
      <table class="admin-table">
        <thead>
          <tr><th>Name</th><th>Mobile</th><th>Pets</th><th>Actions</th></tr>
        </thead>
        <tbody>
          @foreach ($matches as $match)
            <tr>
              <td>{{ $match->full_name }}</td>
              <td>{{ $match->contact_number }}</td>
              <td>{{ $match->pets->pluck('name')->join(', ') ?: '—' }}</td>
              <td><button class="action-view" type="button" onclick="window.location.href='{{ route('staff.walk-ins.customer', $match) }}'">Use This Customer</button></td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <p style="margin-top: 12px; color: #666;">If this is a different person, tick <strong>"This is a different person"</strong> below and save again.</p>
    </div>
  @endif

  <form method="post" action="{{ route('staff.walk-ins.store') }}">
    @csrf
    <div class="admin-table-card">
      <div class="admin-panel-header">
        <h2>Customer</h2>
      </div>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0 15px;">
        <div class="form-group">
          <label for="first_name">First Name</label>
          <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
          @error('first_name') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
        </div>
        <div class="form-group">
          <label for="last_name">Last Name</label>
          <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
          @error('last_name') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
        </div>
        <div class="form-group">
          <label for="contact_number">Mobile Number</label>
          <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" placeholder="09171234567" maxlength="11" required>
          @error('contact_number') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
        </div>
        <div class="form-group">
          <label for="email">Email (optional)</label>
          <input type="email" id="email" name="email" value="{{ old('email') }}">
          @error('email') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
        </div>
      </div>
      <div class="form-group">
        <label for="address">Address (optional)</label>
        <input type="text" id="address" name="address" value="{{ old('address') }}">
        @error('address') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      @if (session('duplicates'))
        <div class="form-group">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
            <input type="checkbox" name="confirm_duplicate" value="1" style="width: auto;">
            This is a different person — register as a new customer anyway
          </label>
        </div>
      @endif
    </div>

    <div class="admin-table-card">
      <div class="admin-panel-header">
        <h2>Pet</h2>
      </div>
      @include('staff.walk-ins.pet-fields')
    </div>

    <div class="admin-table-card">
      <div class="admin-panel-header">
        <h2>Visit</h2>
      </div>
      @include('staff.walk-ins.visit-fields')
    </div>

    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">Register &amp; Add to Board</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.flow.index') }}'">Cancel</button>
    </div>
  </form>
</main>
@endsection
