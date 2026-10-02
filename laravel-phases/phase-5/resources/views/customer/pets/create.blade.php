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
    @include('customer.pets.form-fields')
    <div class="form-buttons">
      <a href="{{ route('portal.pets.index') }}" class="cancel-btn">Cancel</a>
      <button type="submit" class="primary-btn">Save Pet</button>
    </div>
  </form>
</main>
@endsection
