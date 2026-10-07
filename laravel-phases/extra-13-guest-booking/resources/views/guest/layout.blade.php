{{-- Page frame for booking without an account (same look as the login / register pages) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic | @yield('title')</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
  <script src="{{ asset('js/animal.js') }}" defer></script>
  <style>
    .guest-card { max-width: 780px; }
    .guest-card h2 { margin: 26px 0 12px; padding-top: 18px; border-top: 1px solid #f1ebe2; color: #26364a; font-size: 20px; }
    .guest-card h2:first-of-type { margin-top: 6px; padding-top: 0; border-top: none; }
    .guest-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0 15px; }
    .guest-card select, .guest-card textarea { width: 100%; padding: 12px 14px; border: 1px solid #ddd; border-radius: 10px; font-size: 15px; font-family: inherit; background: #fff; }
    .guest-note { margin-top: 6px; color: #777; font-size: 13px; }
    .guest-links { margin-top: 18px; text-align: center; color: #555; font-size: 14px; line-height: 1.8; }
    .guest-links a { color: #e89427; font-weight: bold; }
    .guest-summary { border: 1px solid #eee6db; border-radius: 12px; background: #fffdfa; padding: 14px 18px; margin: 16px 0; }
    .guest-summary div { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; border-bottom: 1px solid #f1ebe2; }
    .guest-summary div:last-child { border-bottom: none; }
    .guest-summary span:first-child { color: #777; }
    .guest-summary span:last-child { font-weight: 600; text-align: right; }
    .guest-ref { margin: 10px auto; padding: 14px; border: 2px dashed #e89427; border-radius: 12px; background: #fff7ec; color: #26364a; font-size: 30px; font-weight: 800; letter-spacing: 2px; text-align: center; }
  </style>
</head>
<body class="account-page">
  <header>
    <h2 class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card guest-card">
      @yield('content')
    </div>
  </main>
</body>
</html>
