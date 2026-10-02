@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | My Pets')
@section('body_class', 'dashboard-page')
@section('content')
<main class="pets-container">
  <div class="page-heading pet-heading">
    <div>
      <h1>My Pets</h1>
      <p>Manage your registered pets.</p>
    </div>
    <a href="{{ route('portal.pets.create') }}" class="primary-btn">+ Add New Pet</a>
  </div>
  @if ($pets->isEmpty())
    <div class="no-history">
      <div class="no-history-icon">🐾</div>
      <h2>No Pets Yet</h2>
      <p>You have not registered a pet. Click "+ Add New Pet" to add your first pet.</p>
    </div>
  @else
    <div class="petlist">
      @foreach ($pets as $pet)
        <div class="pet-card">
          <div class="pet-icon">{{ $pet->icon }}</div>
          <div class="pet-info">
            <h2>{{ $pet->name }}</h2>
            <p>
              <strong>Species:</strong>
              {{ ucfirst($pet->species) }}
            </p>
            <p>
              <strong>Breed:</strong>
              {{ $pet->breed ?: '—' }}
            </p>
            <p>
              <strong>Sex:</strong>
              {{ ucfirst($pet->gender) }}
            </p>
            <p>
              <strong>Age:</strong>
              {{ $pet->age_text }}
            </p>
          </div>
          <div class="pet-actions" style="display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('portal.pets.show', $pet) }}">View Profile</a>
            <a href="{{ route('portal.appointments.create') }}">Book Appointment</a>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</main>
@endsection
