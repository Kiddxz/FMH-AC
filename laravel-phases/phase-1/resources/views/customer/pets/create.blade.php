@extends('layouts.customer')

@section('title', 'FMH Animal Clinic | Add New Pet')
@section('body_class', 'dashboard-page')

@section('content')
<main class="form-page">

  <div class="page-heading">

    <h1>Add New Pet</h1>

    <p>
      Register your pet to manage their information
      and appointments.
    </p>

  </div>

  <form class="pet-form" action="{{ route('portal.pets.store') }}" method="post">
          @csrf

    <h2>Pet Information</h2>

    <div class="form-group">

      <label for="petname">
        Pet Name
      </label>

      <input
        type="text"
        id="petname"
        name="petname"
        placeholder="Enter your pet's name"
        required>

    </div>

    <div class="form-group">

      <label for="species">
        Species
      </label>

      <select
        id="species"
        name="species"
        required>

        <option value="">
          Select species
        </option>

        <option value="dog">
          Dog
        </option>

        <option value="cat">
          Cat
        </option>

        <option value="other">
          Other
        </option>

      </select>

    </div>

    <div class="form-group">

      <label for="breed">
        Breed
      </label>

      <input
        type="text"
        id="breed"
        name="breed"
        placeholder="Enter your pet's breed"
        required>

    </div>

    <div class="form-group">

      <label for="sex">
        Sex
      </label>

      <select
        id="sex"
        name="sex"
        required>

        <option value="">
          Select sex
        </option>

        <option value="male">
          Male
        </option>

        <option value="female">
          Female
        </option>

      </select>

    </div>

    <div class="form-group">

      <label for="age">
        Age
      </label>

      <input
        type="number"
        id="age"
        name="age"
        placeholder="Enter your pet's age"
        min="0"
        max="50"
        required>

    </div>

    <div class="form-group">

      <label for="notes">
        Additional Information
      </label>

      <textarea
        id="notes"
        name="notes"
        rows="4"
        placeholder="Enter any additional information about your pet"></textarea>

    </div>

    <div class="form-buttons">

      <a href="{{ route('portal.pets.index') }}" class="cancel-btn">
        Cancel
      </a>

      <button type="submit" class="primary-btn">
        Save Pet
      </button>

    </div>

  </form>

</main>
@endsection
