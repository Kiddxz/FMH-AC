@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Edit Pet')
@section('body_class', 'dashboard-page')
@section('content')
<main class="form-page">
  <div class="page-heading">
    <h1>Edit {{ $pet->name }}</h1>
    <p>Update your pet's information.</p>
  </div>
  <form class="pet-form" action="{{ route('portal.pets.update', $pet) }}" method="post">
    @csrf
    @method('PUT')
    <h2>Pet Information</h2>
    @include('customer.pets.form-fields')
    <div class="form-buttons">
      <a href="{{ route('portal.pets.show', $pet) }}" class="cancel-btn">Cancel</a>
      <button type="submit" class="primary-btn">Save Changes</button>
    </div>
  </form>
</main>
@endsection
