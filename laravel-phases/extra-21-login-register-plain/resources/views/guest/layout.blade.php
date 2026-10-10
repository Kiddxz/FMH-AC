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
    /* compact form */
    .account-container { padding: 36px 16px; }
    .guest-card { max-width: 700px; padding: 28px 32px; }
    .guest-card h1 { font-size: 26px; margin-bottom: 6px; color: #26364a; }
    .guest-card .account-description { margin-bottom: 18px; font-size: 14px; }
    .guest-card .form-group { margin-bottom: 12px; }
    .guest-card .form-group label { margin-bottom: 5px; font-size: 13.5px; color: #26364a; }
    .guest-card .form-group input, .guest-card .form-group select, .guest-card .form-group textarea { padding: 9px 12px; border-color: #ddd5c8; border-radius: 9px; font-size: 14px; }
    .guest-card .regisbtn { padding: 12px; font-size: 15px; }
    .guest-card .tnc { margin: 12px 0; font-size: 13px; }
    .guest-card h2 { margin: 18px 0 10px; padding-top: 14px; border-top: 1px solid #f1ebe2; color: #e89427; font-size: 15px; letter-spacing: 0.03em; text-transform: uppercase; }
    .guest-card h2:first-of-type { margin-top: 6px; padding-top: 0; border-top: none; }
    .guest-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0 15px; }
    .guest-card select, .guest-card textarea { width: 100%; padding: 9px 12px; border: 1px solid #ddd5c8; border-radius: 9px; font-size: 14px; font-family: inherit; background: #fff; }
    .guest-note { margin-top: 6px; color: #777; font-size: 13px; }
    .guest-links { margin-top: 18px; text-align: center; color: #555; font-size: 14px; line-height: 1.8; }
    .guest-links a { color: #e89427; font-weight: bold; }
    .guest-summary { border: 1px solid #eee6db; border-radius: 12px; background: #fffdfa; padding: 14px 18px; margin: 16px 0; }
    .guest-summary div { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; border-bottom: 1px solid #f1ebe2; }
    .guest-summary div:last-child { border-bottom: none; }
    .guest-summary span:first-child { color: #777; }
    .guest-summary span:last-child { font-weight: 600; text-align: right; }
    .guest-ref { margin: 10px auto; padding: 12px; border: 2px dashed #e89427; border-radius: 12px; background: #fff7ec; color: #26364a; font-size: 26px; font-weight: 800; letter-spacing: 2px; text-align: center; }
  </style>
</head>
<body class="account-page" style="background: #faf6f0;">
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
