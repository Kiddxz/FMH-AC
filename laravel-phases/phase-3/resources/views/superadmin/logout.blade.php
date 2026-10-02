<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic | Super Admin Logout</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
</head>
<body class="superadmin-login-page">
  <main class="superadmin-login-container">
    <div class="superadmin-login-card">
      <div class="superadmin-login-logo">🐾 FMH Animal Clinic</div>
      <h1>Log Out</h1>
      <p class="superadmin-login-description">Are you sure you want to log out of the Super Admin account?</p>
      <form action="{{ route('logout') }}" method="post">
        @csrf
        <button type="submit" class="superadmin-login-btn">Logout</button>
      </form>
      <div class="superadmin-login-back">
        <a href="{{ route('superadmin.dashboard') }}">Cancel</a>
      </div>
    </div>
  </main>
</body>
</html>
