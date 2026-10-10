{{-- DEMO MODE ONLY (PAYMONGO_DEMO=true): a practice page that takes the place of the PayMongo page. No real money. --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Demo Payment | {{ $transaction->receipt_number }}</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
  <style>
    .demo-banner { margin-bottom: 18px; padding: 10px 14px; border: 2px dashed #c46f0c; border-radius: 12px; background: #fff7ec; color: #8a5a12; font-size: 14px; text-align: center; }
    .demo-amount { margin: 8px 0 20px; color: #26364a; font-size: 34px; font-weight: 800; text-align: center; }
    .demo-methods { display: grid; gap: 10px; }
    .demo-methods button { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 14px 18px; border: 1px solid #ddd5c8; border-radius: 12px; background: #fff; color: #26364a; font: inherit; font-size: 16px; font-weight: 700; cursor: pointer; }
    .demo-methods button:hover { border-color: #e89427; background: #fff7ec; }
    .demo-methods small { color: #888; font-size: 12.5px; font-weight: 500; }
  </style>
</head>
<body class="account-page" style="background: #faf6f0;">
  <header>
    <h2 class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card" style="max-width: 460px;">
      <div class="demo-banner"><strong>DEMO MODE</strong><br>This page takes the place of PayMongo while the clinic has no PayMongo account yet. No real money is charged.</div>
      <p style="text-align: center; color: #666;">Bill {{ $transaction->receipt_number }}</p>
      <div class="demo-amount">₱{{ number_format($transaction->balance, 2) }}</div>

      @include('partials.alerts')
      <form method="post" action="{{ route('pay.demo.pay', $transaction->pay_token) }}" class="demo-methods">
        @csrf
        <button type="submit" name="method" value="gcash">GCash <small>practice payment</small></button>
        <button type="submit" name="method" value="paymaya">Maya <small>practice payment</small></button>
        <button type="submit" name="method" value="card">Card <small>practice payment</small></button>
      </form>
      <p style="margin-top: 18px; text-align: center;"><a href="{{ route('pay.show', $transaction->pay_token) }}" style="color: #e89427; font-weight: 700;">Cancel and go back</a></p>
    </div>
  </main>
</body>
</html>
