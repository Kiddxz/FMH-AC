{{-- The bill's pay link (/pay/{token}): what a walk-in customer sees on their phone. No login needed. --}}
@php
  $clinic = \App\Models\Setting::get('clinic_name') ?: 'FMH Animal Clinic';
  $owner = $transaction->customer ? $transaction->customer->first_name . ' ' . mb_substr($transaction->customer->last_name, 0, 1) . '.' : 'Walk-in customer';
  $payable = in_array($transaction->status, ['unpaid', 'partial'], true) && $transaction->balance > 0;
  $row = 'display: flex; justify-content: space-between; gap: 12px; padding: 7px 0;';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta name="referrer" content="no-referrer">
  <title>{{ $clinic }} | Bill {{ $transaction->receipt_number }}</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
</head>
<body class="account-page" style="background: #faf6f0;">
  <header>
    <h2 class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card">
      <h1 style="margin-bottom: 4px;">Your Bill</h1>
      <p class="account-description" style="margin-bottom: 18px;">
        {{ $transaction->receipt_number }} · {{ $transaction->created_at->format('F j, Y') }}<br>
        {{ $owner }}@if ($transaction->pet) · {{ $transaction->pet->name }}@endif
      </p>

      @include('partials.alerts')
      @if ($demo ?? false)
        <p style="margin-bottom: 14px; padding: 8px 12px; border: 1px dashed #c46f0c; border-radius: 10px; background: #fff7ec; color: #8a5a12; font-size: 13px; text-align: center;"><strong>DEMO MODE:</strong> practice payment only. No real money is charged.</p>
      @endif

      <div style="border: 1px solid #eee6db; border-radius: 12px; background: #fffdfa; padding: 14px 18px; margin-bottom: 18px;">
        @foreach ($transaction->items as $item)
          <div style="{{ $row }} border-bottom: 1px solid #f1ebe2;">
            <span>{{ $item->description }}@if ($item->quantity > 1) <span style="color: #888;">× {{ $item->quantity }}</span>@endif</span>
            <span style="white-space: nowrap;">₱{{ number_format($item->line_total, 2) }}</span>
          </div>
        @endforeach
        @if ($transaction->discount > 0)
          <div style="{{ $row }}"><span>Discount</span><span>− ₱{{ number_format($transaction->discount, 2) }}</span></div>
        @endif
        <div style="{{ $row }} font-weight: 700;"><span>Total</span><span>₱{{ number_format($transaction->total, 2) }}</span></div>
        @if ($transaction->amount_paid > 0)
          <div style="{{ $row }}"><span>Already paid</span><span>₱{{ number_format($transaction->amount_paid, 2) }}</span></div>
        @endif
        <div style="{{ $row }} font-size: 19px; font-weight: 700; color: {{ $transaction->balance > 0 ? '#d9534f' : '#287a43' }};">
          <span>Amount to pay</span><span>₱{{ number_format($transaction->balance, 2) }}</span>
        </div>
      </div>

      @if ($transaction->status === 'void')
        <p style="text-align: center; color: #d9534f; font-weight: 700;">This bill was cancelled by the clinic. Please ask the cashier.</p>
      @elseif ($transaction->status === 'paid')
        <p style="text-align: center; color: #287a43; font-weight: 700; font-size: 18px;">✅ Fully paid. Thank you!</p>
        <p style="text-align: center; color: #777; margin-top: 6px;">Please ask the cashier for your official receipt.</p>
      @elseif (! $enabled)
        <p style="text-align: center; color: #777;">Online payment is not available right now. Please pay at the cashier.</p>
      @elseif ($transaction->balance < $minimum)
        <p style="text-align: center; color: #777;">Online payment needs at least ₱{{ number_format($minimum, 2) }}. Please pay this balance at the cashier.</p>
      @elseif ($payable)
        <form method="post" action="{{ route('pay.checkout', $transaction->pay_token) }}">
          @csrf
          <button class="regisbtn" type="submit" style="width: 100%;">Pay ₱{{ number_format($transaction->balance, 2) }} Online</button>
        </form>
        <p style="text-align: center; color: #777; font-size: 13px; margin-top: 10px;">
          GCash · Maya · Card. You will pay on the secure {{ ($demo ?? false) ? 'practice (demo)' : 'PayMongo' }} page.<br>
          You can also pay at the cashier.
        </p>
      @endif
      @if ($transaction->appointment_id && $transaction->customer && ! $transaction->customer->user_id)
        <p style="text-align: center; margin-top: 18px; color: #555; font-size: 14px;">Booking reference: <strong style="color: #26364a; letter-spacing: 1px;">{{ $transaction->appointment?->reference }}</strong><br><span style="font-size: 12.5px; color: #888;">Keep it with your mobile number to check or cancel your booking.</span></p>
        <p style="text-align: center; margin-top: 10px; font-size: 14px;"><a href="{{ route('guest.lookup') }}" style="color: #e89427; font-weight: 700;">Check your booking</a> · <a href="{{ route('home') }}" style="color: #e89427; font-weight: 700;">Home</a></p>
      @endif
    </div>
  </main>
</body>
</html>
