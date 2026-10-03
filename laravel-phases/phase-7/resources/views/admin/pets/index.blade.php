@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Pets')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Pets</h1>
      <p>View registered pets and open their profiles.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-tools" method="get" action="{{ route('admin.pets.index') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search by pet name, breed, or owner...">
    <select name="species" onchange="this.form.submit()">
      <option value="">All Species</option>
      @foreach (\App\Models\Pet::SPECIES as $option)
        <option value="{{ $option }}" @selected($species === $option)>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
    <select name="gender" onchange="this.form.submit()">
      <option value="">All Gender</option>
      <option value="male" @selected($gender === 'male')>Male</option>
      <option value="female" @selected($gender === 'female')>Female</option>
    </select>
    <button class="admin-add-btn" type="submit">Search</button>
  </form>
  <div class="admin-table-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Pet Name</th>
          <th>Breed</th>
          <th>Species</th>
          <th>Gender</th>
          <th>Age</th>
          <th>Owner</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pets as $pet)
          <tr>
            <td>{{ $pet->name }} @if ($pet->status !== 'active') @include('partials.status-badge', ['status' => $pet->status]) @endif</td>
            <td>{{ $pet->breed ?: '—' }}</td>
            <td>{{ ucfirst($pet->species) }}</td>
            <td>{{ ucfirst($pet->gender) }}</td>
            <td>{{ $pet->age_text }}</td>
            <td>{{ $pet->customer?->full_name }}</td>
            <td>
              <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.pets.show', $pet) }}'">View</button>
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
