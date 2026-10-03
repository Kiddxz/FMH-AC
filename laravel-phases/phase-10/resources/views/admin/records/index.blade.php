@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Pet Records')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Pet Records</h1>
      <p>View detailed medical records and health information of registered pets.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-tools" method="get" action="{{ route('admin.records.index') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search by pet, owner, complaint or diagnosis...">
    <select name="species" onchange="this.form.submit()">
      <option value="">All Species</option>
      @foreach (\App\Models\Pet::SPECIES as $option)
        <option value="{{ $option }}" @selected($species === $option)>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
    <button class="action-view" type="submit">Search</button>
    @can('records.write')
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('admin.records.create') }}'">+ Add Pet Record</button>
    @endcan
  </form>
  <div class="admin-table-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Pet Name</th>
          <th>Owner</th>
          <th>Species</th>
          <th>Breed</th>
          <th>Last Visit</th>
          <th>Medical Records</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pets as $pet)
          <tr>
            <td>{{ $pet->name }} @if ($pet->status !== 'active') @include('partials.status-badge', ['status' => $pet->status]) @endif</td>
            <td>{{ $pet->customer?->full_name }}</td>
            <td>{{ ucfirst($pet->species) }}</td>
            <td>{{ $pet->breed ?: '—' }}</td>
            <td>{{ $pet->medical_records_max_record_date ? \Illuminate\Support\Carbon::parse($pet->medical_records_max_record_date)->format('F j, Y') : '—' }}</td>
            <td>{{ \Illuminate\Support\Str::plural('record', $pet->medical_records_count, true) }}</td>
            <td>
              <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.pets.show', $pet) }}'">View</button>
              @can('records.write')
                <button class="action-edit" type="button" onclick="window.location.href='{{ route('admin.records.create', ['pet' => $pet->id]) }}'">+ Record</button>
              @endcan
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; color: #777;">No pets found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
    @include('partials.pager', ['items' => $pets])
  </div>
</main>
@endsection
