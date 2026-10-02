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
      <h2>{{ auth()->user()->first_name }}</h2>
      <p>FMH Animal Clinic Pet Owner</p>
    </div>
    <div class="profile-details">
      <div class="profile-detail">
        <span>👤</span>
        <div>
          <small>Full Name</small>
          <p>{{ auth()->user()->full_name }}</p>
        </div>
      </div>
      <div class="profile-detail">
        <span>📧</span>
        <div>
          <small>Email Address</small>
          <p>{{ auth()->user()->email }}</p>
        </div>
      </div>
      <div class="profile-detail">
        <span>📱</span>
        <div>
          <small>Mobile Number</small>
          <p>{{ auth()->user()->contact_number }}</p>
        </div>
      </div>
    </div>
    <div class="profile-actions">
      <a href="#" class="primary-btn">Edit Profile</a>
      <a href="#" class="cancel-btn" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">Log Out</a>
    </div>
    <form id="logoutForm" action="{{ route('logout') }}" method="post" style="display: none;">
      @csrf
    </form>
  </section>
</main>
@endsection
