@extends('layouts.customer')

@section('title', 'FMH Animal Clinic | My Pets')
@section('body_class', 'dashboard-page')

@section('content')
<main class="pets-container">

  <div class="page-heading pet-heading">

    <div>

      <h1>My Pets</h1>

      <p>
        Manage your registered pets.
      </p>

    </div>

    <a href="{{ route('portal.pets.create') }}" class="primary-btn">
      + Add New Pet
    </a>

  </div>

  <div class="petlist">

    <div class="pet-card">

      <div class="pet-icon">
        🐶
      </div>

      <div class="pet-info">

        <h2>Buddy</h2>

        <p>
          <strong>Species:</strong>
          Dog
        </p>

        <p>
          <strong>Breed:</strong>
          Golden Retriever
        </p>

        <p>
          <strong>Sex:</strong>
          Male
        </p>

        <p>
          <strong>Age:</strong>
          3 years old
        </p>

      </div>

      <div class="pet-actions">

        <a href="{{ route('portal.appointments.create') }}">
          Book Appointment
        </a>

      </div>

    </div>

  </div>

</main>
@endsection
