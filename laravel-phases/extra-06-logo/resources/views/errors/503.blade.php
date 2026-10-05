{{-- "Under Maintenance" page (Phase 17). Shown to everybody except the Super Admin while maintenance mode is on. --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic | Under Maintenance</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
</head>
<body class="account-page">
  <header>
    <h2 class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card" style="text-align: center;">
      <div class="forgot-icon">🛠️</div>
      <h1>Under Maintenance</h1>
      <p class="account-description">{{ ($message ?? null) ?: 'The system is under maintenance. Please try again later.' }}</p>
      @auth
        <form method="post" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="resetbtn">Log Out</button>
        </form>
      @else
        <a href="{{ route('home') }}" class="resetbtn" style="text-decoration: none;">Back to Home</a>
      @endauth
    </div>
  </main>
</body>
</html>
