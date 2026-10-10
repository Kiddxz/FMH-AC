<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic | Forgot Password</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
</head>
<body class="account-page" style="background: #faf6f0;">
  <header>
    <h2 class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card">
      <div class="forgot-icon">🔐</div>
      <h1>Forgot Password?</h1>
      <p class="account-description">Enter your email address and we will send you a 6-digit code to reset your password.</p>
      @include('partials.alerts')
      <form action="{{ route('password.email') }}" method="post">
        @csrf
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" autocomplete="email" required>
        </div>
        <button class="resetbtn" type="submit">Send Reset Code</button>
      </form>
      <div class="divider">
        <span>OR</span>
      </div>
      <div class="relog">
        <p>
          Remember your password?
          <a href="{{ route('login') }}">Back to Login</a>
        </p>
      </div>
    </div>
  </main>
</body>
</html>
