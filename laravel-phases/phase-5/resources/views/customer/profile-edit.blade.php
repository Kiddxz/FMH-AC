@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | Edit Profile')
@section('body_class', 'dashboard-page')
@section('content')
<main class="form-page">
  <div class="page-heading">
    <h1>Edit Profile</h1>
    <p>Update your contact information or change your password.</p>
  </div>

  <form class="pet-form" action="{{ route('portal.profile.update') }}" method="post" style="margin-bottom: 30px;">
    @csrf
    @method('PUT')
    <h2>Personal Information</h2>
    <div class="form-group">
      <label for="firstname">First Name</label>
      <input type="text" id="firstname" name="firstname" value="{{ old('firstname', $user->first_name) }}" autocomplete="given-name" required>
    </div>
    <div class="form-group">
      <label for="lastname">Last Name</label>
      <input type="text" id="lastname" name="lastname" value="{{ old('lastname', $user->last_name) }}" autocomplete="family-name" required>
    </div>
    <div class="form-group">
      <label for="email">Email Address</label>
      <input type="email" id="email" value="{{ $user->email }}" disabled style="background: #f3f3f3; color: #777;">
      <small style="color: #777;">Your email is your login name. To change it, please contact the clinic.</small>
    </div>
    <div class="form-group">
      <label for="mobile">Mobile Number</label>
      <input type="tel" id="mobile" name="mobile" value="{{ old('mobile', $user->contact_number) }}" placeholder="09XXXXXXXXX" autocomplete="tel" required>
    </div>
    <div class="form-group">
      <label for="address">Address</label>
      <input type="text" id="address" name="address" value="{{ old('address', $customer?->address) }}" placeholder="House no., street, barangay, city" autocomplete="street-address">
    </div>
    <div class="form-buttons">
      <a href="{{ route('portal.profile') }}" class="cancel-btn">Cancel</a>
      <button type="submit" class="primary-btn">Save Profile</button>
    </div>
  </form>

  <form class="pet-form" action="{{ route('portal.password.update') }}" method="post">
    @csrf
    @method('PUT')
    <h2>Change Password</h2>
    <div class="form-group">
      <label for="current_password">Current Password</label>
      <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
    </div>
    <div class="form-group">
      <label for="password">New Password</label>
      <input type="password" id="password" name="password" placeholder="At least 8 characters, letters and numbers" autocomplete="new-password" required>
    </div>
    <div class="form-group">
      <label for="password_confirmation">Confirm New Password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
    </div>
    <div class="form-buttons">
      <button type="submit" class="primary-btn">Change Password</button>
    </div>
  </form>
</main>
@endsection
