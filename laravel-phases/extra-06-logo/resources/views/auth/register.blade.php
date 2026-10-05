<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic | Register</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
  <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
<body class="account-page">
  <header>
    <h2 class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card">
      <h1>Create Account</h1>
      <p class="account-description">Join FMH Animal Clinic by creating your account.</p>
      @include('partials.alerts')
      <form action="{{ route('register.store') }}" method="post">
        @csrf
        <div class="form-row">
          <div class="form-group">
            <label for="firstname">First Name</label>
            <input type="text" id="firstname" name="firstname" value="{{ old('firstname') }}" placeholder="First name" autocomplete="given-name" required>
          </div>
          <div class="form-group">
            <label for="lastname">Last Name</label>
            <input type="text" id="lastname" name="lastname" value="{{ old('lastname') }}" placeholder="Last name" autocomplete="family-name" required>
          </div>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" autocomplete="email" required>
        </div>
        <div class="form-group">
          <label for="mobile">Mobile Number</label>
          <input type="tel" id="mobile" name="mobile" value="{{ old('mobile') }}" placeholder="09XXXXXXXXX" autocomplete="tel" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="At least 8 characters, letters and numbers" autocomplete="new-password" required>
        </div>
        <div class="form-group">
          <label for="confirmpass">Confirm Password</label>
          <input type="password" id="confirmpass" name="password_confirmation" placeholder="Confirm your password" autocomplete="new-password" required>
        </div>
        <div class="tnc">
          <label>
            <input type="checkbox" id="tnc" name="tnc" value="1" {{ old('tnc') ? 'checked' : '' }} required>
            <span>
              I agree to the
              <a href="#">Terms & Conditions</a>
            </span>
          </label>
        </div>
        <button class="regisbtn" type="submit">Create Account</button>
      </form>
      <div class="divider">
        <span>OR</span>
      </div>
      <div class="relog">
        <p>
          Already have an account?
          <a href="{{ route('login') }}">Login</a>
        </p>
      </div>
    </div>
  </main>
</body>
</html>
