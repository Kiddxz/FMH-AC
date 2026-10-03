@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Pet Records')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Digital Pet Records</h1>
      <p>Monitor digital pet records. Medical notes are private to the veterinarians.</p>
    </div>
  </div>
  <form class="superadmin-tools" method="get" action="{{ route('superadmin.pet-records') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search pet or owner...">
    <select name="species" onchange="this.form.submit()">
      <option value="">All Species</option>
      @foreach (\App\Models\Pet::SPECIES as $option)
        <option value="{{ $option }}" @selected($species === $option)>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
    <button type="submit" class="superadmin-add-btn">Search</button>
  </form>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Pet Name</th>
          <th>Owner</th>
          <th>Species</th>
          <th>Breed</th>
          <th>Medical History</th>
          <th>Vaccination</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pets as $pet)
          <tr>
            <td>{{ $pet->name }}</td>
            <td>{{ $pet->customer?->full_name }}</td>
            <td>{{ ucfirst($pet->species) }}</td>
            <td>{{ $pet->breed ?: '—' }}</td>
            <td>{{ $pet->medical_records_count ? $pet->medical_records_count . ' ' . \Illuminate\Support\Str::plural('record', $pet->medical_records_count) : 'None yet' }}</td>
            <td>{{ $pet->vaccinations_count ? $pet->vaccinations_count . ' ' . \Illuminate\Support\Str::plural('vaccine', $pet->vaccinations_count) : 'None yet' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; color: #94a3b8;">No pets found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.superadmin-pager', ['items' => $pets])
</main>
@endsection
