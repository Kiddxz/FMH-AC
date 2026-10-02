{{-- "Edit Profile" + "Change Password" forms for clinic accounts (Staff, Vet/Admin, Super Admin).
     Use: @include('partials.profile-forms', ['area' => 'staff'])   ('staff', 'admin' or 'superadmin') --}}
<form class="admin-table-card" action="{{ route($area . '.profile.update') }}" method="post">
  @csrf
  @method('PUT')
  <div class="admin-panel-header">
    <div>
      <h2>Personal Information</h2>
      <p>Your email is your login name. Ask the Super Admin if it must be changed.</p>
    </div>
  </div>
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
  </div>
  <div class="form-group">
    <label for="mobile">Mobile Number</label>
    <input type="tel" id="mobile" name="mobile" value="{{ old('mobile', $user->contact_number) }}" placeholder="09XXXXXXXXX" autocomplete="tel" required>
  </div>
  <div class="admin-tools">
    <button class="admin-add-btn" type="submit">Save Profile</button>
    <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.profile') }}'">Cancel</button>
  </div>
</form>

<form class="admin-table-card" id="password" action="{{ route($area . '.password.update') }}" method="post">
  @csrf
  @method('PUT')
  <div class="admin-panel-header">
    <div>
      <h2>Change Password</h2>
      <p>At least 8 characters with letters and numbers, different from your current password.</p>
    </div>
  </div>
  <div class="form-group">
    <label for="current_password">Current Password</label>
    <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
  </div>
  <div class="form-group">
    <label for="password_new">New Password</label>
    <input type="password" id="password_new" name="password" autocomplete="new-password" required>
  </div>
  <div class="form-group">
    <label for="password_confirmation">Confirm New Password</label>
    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
  </div>
  <div class="admin-tools">
    <button class="admin-add-btn" type="submit">Change Password</button>
  </div>
</form>
