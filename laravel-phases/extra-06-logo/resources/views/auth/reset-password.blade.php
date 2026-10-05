<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic | Reset Password</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
  <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
<body class="account-page">
  <header>
    <h2 class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card">
      <div class="forgot-icon">🔐</div>
      <h1>Reset Password</h1>
      <p class="account-description">Enter the 6-digit code sent to <strong>{{ $email }}</strong>, then choose a new password.</p>
      @include('partials.alerts')
      <form action="{{ route('password.update') }}" method="post">
        @csrf
        <div class="form-group">
          <label for="code">Reset Code</label>
          <input type="text" id="code" name="code" placeholder="Enter the 6-digit code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
        </div>
        <div class="form-group">
          <label for="password">New Password</label>
          <input type="password" id="password" name="password" placeholder="At least 8 characters, letters and numbers" autocomplete="new-password" required>
        </div>
        <div class="form-group">
          <label for="password_confirmation">Confirm New Password</label>
          <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm your new password" autocomplete="new-password" required>
        </div>
        <button class="resetbtn" type="submit">Reset Password</button>
      </form>
      <div class="divider">
        <span>OR</span>
      </div>
      <div class="relog">
        <p>
          Didn't get the code?
          <a href="{{ route('password.request') }}">Send again</a>
        </p>
        <p>
          Remember your password?
          <a href="{{ route('login') }}">Back to Login</a>
        </p>
      </div>
    </div>
  </main>
</body>
</html>
