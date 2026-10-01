@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Logout')
@section('body_class', 'admin-logout-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="logout-container">
  <div class="logout-card">
    <div class="logout-icon">🚪</div>
    <h1>Logout</h1>
    <p>Are you sure you want to log out of the admin panel?</p>
    <a href="{{ route('login') }}" class="logout-btn">Yes, Log Out</a>
  </div>
</main>
@endsection
