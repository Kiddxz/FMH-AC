@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Edit Profile')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Edit Profile</h1>
      <p>Update your details or change your password.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  @include('partials.profile-forms', ['area' => 'admin'])
</main>
@endsection
