@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Edit Customer')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Edit Customer</h1>
      <p>Update the pet owner's contact information.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-table-card" action="{{ route('staff.customers.update', $customer) }}" method="post">
    @csrf
    @method('PUT')
    <div class="form-group">
      <label for="firstname">First Name</label>
      <input type="text" id="firstname" name="firstname" value="{{ old('firstname', $customer->first_name) }}" required>
    </div>
    <div class="form-group">
      <label for="lastname">Last Name</label>
      <input type="text" id="lastname" name="lastname" value="{{ old('lastname', $customer->last_name) }}" required>
    </div>
    <div class="form-group">
      <label for="mobile">Mobile Number</label>
      <input type="tel" id="mobile" name="mobile" value="{{ old('mobile', $customer->contact_number) }}" placeholder="09XXXXXXXXX" required>
    </div>
    <div class="form-group">
      <label for="email">Email Address</label>
      @if ($customer->user_id)
        <input type="email" id="email" value="{{ $customer->email }}" disabled style="background: #f3f3f3; color: #777;">
        <small style="color: #777;">This is the customer's login email, so it cannot be changed here.</small>
      @else
        <input type="email" id="email" name="email" value="{{ old('email', $customer->email) }}" placeholder="Optional">
      @endif
    </div>
    <div class="form-group">
      <label for="address">Address</label>
      <input type="text" id="address" name="address" value="{{ old('address', $customer->address) }}" placeholder="House no., street, barangay, city">
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">Save Changes</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.customers.show', $customer) }}'">Cancel</button>
    </div>
  </form>
</main>
@endsection
