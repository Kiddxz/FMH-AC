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
    .pay-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; }
    .pay-option { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border: 1px solid #ddd5c8; border-radius: 12px; background: #fff; cursor: pointer; }
    .pay-option:has(input:checked) { border-color: #e89427; background: #fff7ec; box-shadow: 0 0 0 2px rgba(232, 148, 39, 0.15); }
    .pay-option.disabled { opacity: 0.55; cursor: not-allowed; }
    .pay-option input { width: auto; margin-top: 3px; accent-color: #e89427; }
    .pay-option strong { display: block; color: #26364a; font-size: 14.5px; }
    .pay-option small { display: block; margin-top: 2px; color: #777; font-size: 12.5px; line-height: 1.4; }
    .guest-pay { margin: 14px 0; padding: 12px 16px; border-radius: 12px; background: #fff7ec; text-align: center; }
    .guest-pay a { display: inline-block; margin-top: 8px; padding: 10px 22px; border-radius: 10px; background: #e89427; color: #fff; font-weight: 700; text-decoration: none; }
    .guest-links { margin-top: 18px; text-align: center; color: #555; font-size: 14px; line-height: 1.8; }
    .guest-links a { color: #e89427; font-weight: bold; }
    .guest-summary { border: 1px solid #eee6db; border-radius: 12px; background: #fffdfa; padding: 14px 18px; margin: 16px 0; }
    .guest-summary div { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; border-bottom: 1px solid #f1ebe2; }
    .guest-summary div:last-child { border-bottom: none; }
    .guest-summary span:first-child { color: #777; }
    .guest-summary span:last-child { font-weight: 600; text-align: right; }
    .bk-hero { text-align: center; }
    .bk-hero h1 { font-size: 30px; }
    .bk-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin: 22px 0 8px; }
    .bk-action { display: block; padding: 18px; border: 1px solid #eee6db; border-radius: 14px; background: #fff; color: #26364a; text-align: left; text-decoration: none; transition: border-color 0.2s, transform 0.2s; }
    .bk-action:hover { border-color: #e89427; transform: translateY(-2px); }
    .bk-action strong { display: block; font-size: 17px; }
    .bk-action small { display: block; margin-top: 4px; color: #777; font-size: 13px; line-height: 1.45; }
    .bk-action.primary { border-color: #e89427; background: #e89427; color: #fff; }
    .bk-action.primary small { color: #fff3e0; }
    .bk-steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; }
    .bk-steps div { padding: 12px 14px; border-radius: 12px; background: #fffdfa; border: 1px solid #f1ebe2; font-size: 13.5px; color: #4b5563; }
    .bk-steps b { display: block; color: #e89427; font-size: 13px; }
    .bk-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .bk-chips span { padding: 5px 10px; border-radius: 999px; background: #fff1df; color: #8a5a12; font-size: 13px; font-weight: 600; }
    /* walk-in home page */
    .wk { max-width: 1120px; margin: 0 auto; padding: 48px 20px 40px; color: #26364a; }
    .wk-hero { display: grid; grid-template-columns: 1.25fr 1fr; gap: 28px; align-items: center; }
    .wk-pill { display: inline-block; padding: 6px 14px; border-radius: 999px; background: #fff1df; color: #c46f0c; font-size: 13px; font-weight: 700; }
    .wk-hero h1 { margin: 0 0 12px; font-size: 44px; line-height: 1.15; }
    .wk-hero h1 span { color: #e89427; }
    .wk-hero p { color: #4b5563; font-size: 17px; line-height: 1.6; }
    .wk-buttons { display: flex; flex-wrap: wrap; gap: 12px; margin: 24px 0 18px; }
    .wk-btn { display: inline-flex; align-items: center; gap: 8px; padding: 14px 26px; border-radius: 12px; font-size: 16px; font-weight: 700; text-decoration: none; transition: transform 0.2s, background 0.2s; }
    .wk-btn:hover { transform: translateY(-2px); }
    .wk-btn.primary { background: #e89427; color: #fff; box-shadow: 0 10px 22px rgba(232, 148, 39, 0.28); }
    .wk-btn.primary:hover { background: #d57d13; }
    .wk-btn.outline { border: 2px solid #e2d6c4; background: #fff; color: #26364a; }
    .wk-btn.outline:hover { border-color: #e89427; }
    .wk-btn svg, .wk-trust svg, .wk-card h3 svg { width: 18px; height: 18px; flex-shrink: 0; }
    .wk-trust { display: flex; flex-wrap: wrap; gap: 8px 18px; color: #4b5563; font-size: 14px; }
    .wk-trust span { display: inline-flex; align-items: center; gap: 6px; }
    .wk-trust svg { color: #287a43; }
    .wk-card { padding: 22px 24px; border: 1px solid #eee6db; border-radius: 18px; background: #fff; box-shadow: 0 12px 30px rgba(38, 54, 74, 0.07); }
    .wk-card h3 { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 17px; }
    .wk-card h3 svg { color: #e89427; }
    .wk-today { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; padding: 12px 14px; border-radius: 12px; background: #fff7ec; }
    .wk-today strong { font-size: 15px; }
    .wk-badge { padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .wk-badge.open { background: #e6f5e9; color: #287a43; }
    .wk-badge.closed { background: #fdecec; color: #b42318; }
    .wk-hours div { display: flex; justify-content: space-between; gap: 10px; padding: 7px 0; border-top: 1px solid #f1ebe2; font-size: 14.5px; }
    .wk-hours div:first-child { border-top: none; }
    .wk-hours span:first-child { color: #6b7280; }
    .wk-hours span:last-child { font-weight: 600; }
    .wk-call { margin-top: 14px; padding-top: 12px; border-top: 1px dashed #e2d6c4; font-size: 14px; color: #4b5563; }
    .wk-call a { color: #26364a; font-weight: 700; text-decoration: none; }
    .wk-section { margin-top: 52px; }
    .wk-section > h2 { margin-bottom: 6px; font-size: 26px; }
    .wk-section > p { margin-bottom: 20px; color: #6b7280; }
    .wk-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .wk-step { padding: 20px; border: 1px solid #eee6db; border-radius: 16px; background: #fff; }
    .wk-step b { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; margin-bottom: 10px; border-radius: 50%; background: #e89427; color: #fff; font-size: 15px; }
    .wk-step strong { display: block; margin-bottom: 4px; font-size: 16px; }
    .wk-step span { color: #6b7280; font-size: 14px; line-height: 1.5; }
    .wk-services { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
    .wk-service { display: flex; flex-direction: column; gap: 6px; padding: 18px; border: 1px solid #eee6db; border-radius: 16px; background: #fff; color: #26364a; text-decoration: none; transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s; }
    .wk-service:hover { border-color: #e89427; transform: translateY(-3px); box-shadow: 0 12px 24px rgba(232, 148, 39, 0.15); }
    .wk-service strong { font-size: 16px; }
    .wk-service small { color: #6b7280; font-size: 13px; }
    .wk-service .price { color: #e89427; font-size: 18px; font-weight: 800; }
    .wk-service .go { margin-top: auto; padding-top: 6px; color: #e89427; font-size: 13.5px; font-weight: 700; }
    .wk-foot { margin-top: 52px; padding: 22px; border-radius: 18px; background: #26364a; color: #fff; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
    .wk-foot p { color: #cfd8e3; font-size: 14.5px; }
    .wk-foot a { display: inline-block; padding: 10px 18px; border-radius: 10px; background: #fff; color: #26364a; font-weight: 700; text-decoration: none; }
    .wk-foot a.ghost { background: transparent; border: 1px solid #5b6b80; color: #fff; }
    @media (max-width: 860px) {
      .wk-hero { grid-template-columns: 1fr; }
      .wk-hero h1 { font-size: 34px; }
      .wk-steps { grid-template-columns: 1fr; }
    }
    .guest-ref { margin: 10px auto; padding: 12px; border: 2px dashed #e89427; border-radius: 12px; background: #fff7ec; color: #26364a; font-size: 26px; font-weight: 800; letter-spacing: 2px; text-align: center; }
  </style>
</head>
<body class="account-page" style="background: #faf6f0;">
  <header>
    <a href="{{ route('guest.home') }}" class="logo" style="text-decoration: none;"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</a>
  </header>
  @hasSection('wide')
    @yield('wide')
  @else
    <main class="account-container">
      <div class="account-card guest-card">
        @yield('content')
      </div>
    </main>
  @endif
</body>
</html>
