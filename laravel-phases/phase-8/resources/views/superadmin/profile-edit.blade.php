@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Edit Profile')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container" style="max-width: 900px;">
  <div class="superadmin-page-heading">
    <div>
      <h1>Edit Profile</h1>
      <p>Update your details or change your password.</p>
    </div>
  </div>
  @include('partials.profile-forms', ['area' => 'superadmin'])
</main>
@endsection
