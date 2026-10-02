{{-- Shared design for the error pages (403, 404, 419), same style as the login pages. --}}
@php
    $home = auth()->check() ? auth()->user()->homeUrl() : route('home');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic | @yield('title')</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
</head>
<body class="account-page">
  <header>
    <h2 class="logo">🐾 FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card" style="text-align: center;">
      <div class="forgot-icon">@yield('icon')</div>
      <h1>@yield('heading')</h1>
      <p class="account-description">@yield('message')</p>
      <a href="{{ $home }}" class="resetbtn" style="text-decoration: none;">
        {{ auth()->check() ? 'Back to My Dashboard' : 'Back to Home' }}
      </a>
    </div>
  </main>
</body>
</html>
