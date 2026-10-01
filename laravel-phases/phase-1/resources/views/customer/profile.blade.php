@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Profile')
@section('body_class', 'dashboard-page')
@section('content')
<main class="profile-container">
  <div class="page-heading">
    <h1>My Profile</h1>
    <p>Manage your personal account information.</p>
  </div>
  <section class="profile-card">
    <div class="profile-avatar">👤</div>
    <div class="profile-info">
      <h2>Mark</h2>
      <p>FMH Animal Clinic Pet Owner</p>
    </div>
    <div class="profile-details">
      <div class="profile-detail">
        <span>👤</span>
        <div>
          <small>Full Name</small>
          <p>Mark</p>
        </div>
      </div>
      <div class="profile-detail">
        <span>📧</span>
        <div>
          <small>Email Address</small>
          <p>mark@example.com</p>
        </div>
      </div>
      <div class="profile-detail">
        <span>📱</span>
        <div>
          <small>Mobile Number</small>
          <p>09XXXXXXXXX</p>
        </div>
      </div>
    </div>
    <div class="profile-actions">
      <a href="#" class="primary-btn">Edit Profile</a>
      <a href="{{ route('login') }}" class="cancel-btn">Log Out</a>
    </div>
  </section>
</main>
@endsection
