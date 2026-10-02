<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic | Verify Email</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
</head>
<body class="account-page">
  <header>
    <h2 class="logo">🐾 FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card">
      <div class="forgot-icon">📧</div>
      <h1>Verify Your Email</h1>
      <p class="account-description">Enter the 6-digit code we sent to <strong>{{ $email }}</strong>. The code expires in 10 minutes.</p>
      @include('partials.alerts')
      <form action="{{ route('register.verify.check') }}" method="post">
        @csrf
        <div class="form-group">
          <label for="code">Verification Code</label>
          <input type="text" id="code" name="code" placeholder="Enter the 6-digit code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
        </div>
        <button class="resetbtn" type="submit">Verify Email</button>
      </form>
      <div class="divider">
        <span>OR</span>
      </div>
      <div class="relog">
        <form action="{{ route('register.resend') }}" method="post">
          @csrf
          <p>
            Didn't get the code?
            <button type="submit" style="border: none; background: none; padding: 0; color: #e89427; font-weight: bold; font-size: inherit; font-family: inherit; cursor: pointer;">Send a new code</button>
          </p>
        </form>
        <p>
          Wrong email?
          <a href="{{ route('register') }}">Register again</a>
        </p>
      </div>
    </div>
  </main>
</body>
</html>
