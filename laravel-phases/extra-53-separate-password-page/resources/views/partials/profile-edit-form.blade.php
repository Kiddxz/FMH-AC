{{-- "Edit Profile" page or "Change Password" page, the same design for every user (Pet Owner, Staff, Vet/Admin, Super Admin).
     Use: @include('partials.profile-edit-form', ['area' => 'staff', 'mode' => 'profile', 'user' => $user])
          @include('partials.profile-edit-form', ['area' => 'staff', 'mode' => 'password', 'user' => $user])
     'area' is 'staff', 'admin', 'superadmin' or 'portal' (the Pet Owner also passes 'customer' => $customer for the address).
     Each page only shows ONE form: the profile details OR the password. --}}
@php
  $isPassword = ($mode ?? 'profile') === 'password';
  $initials = mb_strtoupper(mb_substr((string) $user->first_name, 0, 1) . mb_substr((string) $user->last_name, 0, 1)) ?: '?';
  $profileUrl = route($area . '.profile');
  $icons = [
    'lock' => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
  ];
  $svg = fn (string $name, int $size = 20) => '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? '') . '</svg>';
@endphp
@once
<style>
  .pe-wrap { width: 100%; max-width: 760px; margin: 0 auto; padding: 36px 28px 50px; box-sizing: border-box; }
  .pe-back { display: inline-block; margin-bottom: 16px; color: #64748b; font-size: 14px; font-weight: 600; text-decoration: none; }
  .pe-back:hover { color: #c46f0c; }
  .pe-card { overflow: hidden; border: 1px solid #eee6db; border-radius: 16px; background: #fff; box-shadow: 0 2px 10px rgba(38, 54, 74, 0.05); }
  .pe-head { display: flex; align-items: center; gap: 16px; padding: 22px 26px; border-bottom: 1px solid #f3eee7; background: linear-gradient(120deg, #fffaf3 0%, #fff1df 100%); }
  .pe-head-icon { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 52px; height: 52px; border-radius: 50%; background: #26364a; color: #fff; font-size: 18px; font-weight: 800; letter-spacing: 0.5px; }
  .pe-head-icon.lock { background: #e89427; }
  .pe-head h1 { margin: 0; color: #26364a; font-size: 21px; font-weight: 800; }
  .pe-head p { margin: 4px 0 0; color: #64748b; font-size: 14px; }
  .pe-body { padding: 24px 26px 8px; }
  .pe-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 18px; }
  .pe-field { margin-bottom: 18px; }
  .pe-field.full { grid-column: 1 / -1; }
  .pe-field label { display: block; margin-bottom: 7px; color: #26364a; font-size: 14px; font-weight: 700; }
  .pe-wrap .pe-field input { width: 100%; height: 46px; padding: 0 14px; border: 1px solid #ddd5ca; border-radius: 10px; background: #fff; color: #26364a; font-family: inherit; font-size: 15px; box-sizing: border-box; transition: border-color 0.15s, box-shadow 0.15s; }
  .pe-wrap .pe-field input:focus { outline: none; border-color: #e89427; box-shadow: 0 0 0 3px rgba(232, 148, 39, 0.15); }
  .pe-wrap .pe-field input:disabled { background: #f6f4f1; color: #8a8f98; cursor: not-allowed; }
  .pe-wrap .pe-field input.is-invalid { border-color: #d9534f; }
  .pe-field .pe-hint { display: block; margin-top: 6px; color: #8a8f98; font-size: 12.5px; }
  .pe-field .pe-error { color: #b03a37; }
  .pe-field .pw-help { margin-top: 10px; }
  .pe-foot { display: flex; justify-content: flex-end; gap: 10px; padding: 18px 26px; border-top: 1px solid #f3eee7; background: #fffdfa; }
  .pe-btn { display: inline-flex; align-items: center; justify-content: center; height: 44px; padding: 0 22px; border: 1px solid #eee6db; border-radius: 10px; background: #fff; color: #26364a; font-family: inherit; font-size: 14.5px; font-weight: 700; text-decoration: none; cursor: pointer; transition: background 0.15s, border-color 0.15s, color 0.15s; }
  .pe-btn:hover { border-color: #e89427; color: #c46f0c; background: #fffaf3; }
  .pe-btn.primary { border-color: #e89427; background: #e89427; color: #fff; }
  .pe-btn.primary:hover { background: #d57d13; color: #fff; }
  .pe-note { margin: 16px 4px 0; color: #8a8f98; font-size: 13px; text-align: center; }
  @media (max-width: 700px) {
    .pe-wrap { padding: 22px 16px 40px; }
    .pe-head { padding: 18px; }
    .pe-body { padding: 20px 18px 4px; }
    .pe-grid { grid-template-columns: 1fr; }
    .pe-foot { padding: 16px 18px; }
    .pe-foot .pe-btn { flex: 1; }
  }
</style>
@endonce
<main class="pe-wrap">
  <a href="{{ $profileUrl }}" class="pe-back">Back to My Profile</a>

  @if (! $isPassword)
    {{-- Edit Profile: personal details only --}}
    <form class="pe-card" action="{{ route($area . '.profile.update') }}" method="post">
      @csrf
      @method('PUT')
      <div class="pe-head">
        <span class="pe-head-icon" aria-hidden="true">{{ $initials }}</span>
        <div>
          <h1>Edit Profile</h1>
          <p>Update your name and contact details.</p>
        </div>
      </div>
      <div class="pe-body pe-grid">
        <div class="pe-field">
          <label for="firstname">First Name</label>
          <input type="text" id="firstname" name="firstname" value="{{ old('firstname', $user->first_name) }}" autocomplete="given-name" class="@error('firstname') is-invalid @enderror" required>
          @error('firstname')<small class="pe-hint pe-error">{{ $message }}</small>@enderror
        </div>
        <div class="pe-field">
          <label for="lastname">Last Name</label>
          <input type="text" id="lastname" name="lastname" value="{{ old('lastname', $user->last_name) }}" autocomplete="family-name" class="@error('lastname') is-invalid @enderror" required>
          @error('lastname')<small class="pe-hint pe-error">{{ $message }}</small>@enderror
        </div>
        <div class="pe-field">
          <label for="email">Email Address</label>
          <input type="email" id="email" value="{{ $user->email }}" disabled>
          <small class="pe-hint">Your email is your login name. {{ $area === 'portal' ? 'Contact the clinic' : 'Ask the Super Admin' }} if it must be changed.</small>
        </div>
        <div class="pe-field">
          <label for="mobile">Mobile Number</label>
          <input type="tel" id="mobile" name="mobile" value="{{ old('mobile', $user->contact_number) }}" placeholder="09XXXXXXXXX" autocomplete="tel" class="@error('mobile') is-invalid @enderror" required>
          @error('mobile')<small class="pe-hint pe-error">{{ $message }}</small>@enderror
        </div>
        @if ($area === 'portal')
          <div class="pe-field full">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="{{ old('address', $customer?->address) }}" placeholder="House no., street, barangay, city" autocomplete="street-address" class="@error('address') is-invalid @enderror">
            @error('address')<small class="pe-hint pe-error">{{ $message }}</small>@enderror
          </div>
        @endif
      </div>
      <div class="pe-foot">
        <a href="{{ $profileUrl }}" class="pe-btn">Cancel</a>
        <button type="submit" class="pe-btn primary">Save Changes</button>
      </div>
    </form>
  @else
    {{-- Change Password: password only --}}
    <form class="pe-card" action="{{ route($area . '.password.update') }}" method="post">
      @csrf
      @method('PUT')
      <div class="pe-head">
        <span class="pe-head-icon lock" aria-hidden="true">{!! $svg('lock', 24) !!}</span>
        <div>
          <h1>Change Password</h1>
          <p>For your safety, you will be logged out on your other devices.</p>
        </div>
      </div>
      <div class="pe-body">
        <div class="pe-field">
          <label for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" autocomplete="current-password" class="@error('current_password') is-invalid @enderror" required>
          @error('current_password')<small class="pe-hint pe-error">{{ $message }}</small>@enderror
        </div>
        <div class="pe-field">
          <label for="password_new">New Password</label>
          <input type="password" id="password_new" name="password" autocomplete="new-password" class="@error('password') is-invalid @enderror" required>
          @error('password')<small class="pe-hint pe-error">{{ $message }}</small>@enderror
        </div>
        <div class="pe-field">
          <label for="password_confirmation">Confirm New Password</label>
          <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>
      </div>
      <div class="pe-foot">
        <a href="{{ $profileUrl }}" class="pe-btn">Cancel</a>
        <button type="submit" class="pe-btn primary">Change Password</button>
      </div>
    </form>
    <p class="pe-note">Forgot your current password? Log out and use "Forgot Password" on the login page.</p>
  @endif
</main>
