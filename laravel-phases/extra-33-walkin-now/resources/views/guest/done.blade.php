@extends('guest.layout')
@section('title', 'Booking Received')
@section('content')
  <h1>Booking Received!</h1>
  <p class="account-description">Thank you, {{ $appointment->customer->first_name }}. {{ $appointment->pet->name }} is now in the clinic's queue. Please wait to be called.</p>

  @if ($visit)
    <p style="text-align: center; color: #555;">Your queue number:</p>
    <div class="guest-ref">#{{ $visit->queue_number }}</div>
  @endif
  <p style="text-align: center; color: #555;">Your reference number: <strong>{{ $appointment->reference }}</strong></p>
  <p class="guest-note" style="text-align: center;">Take a screenshot or write it down. You need it and your mobile number to check this booking.</p>

  <div class="guest-summary">
    <div><span>Pet</span><span>{{ $appointment->pet->name }}</span></div>
    <div><span>Services</span><span>{{ $appointment->service_names }}</span></div>
    <div><span>Date</span><span>{{ $appointment->appointment_date->format('l, F j, Y') }}</span></div>
    <div><span>Time booked</span><span>{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</span></div>
    <div><span>Estimated total</span><span>₱{{ number_format($appointment->total_price, 2) }}{{ $bill ? '' : ' (pay at the clinic)' }}</span></div>
    <div><span>Status</span><span>{{ ucfirst($appointment->status) }}</span></div>
  </div>

  @if ($bill)
    <div class="guest-pay">
      @if ($bill->status === 'paid')
        <strong style="color: #287a43;">Paid online: ₱{{ number_format($bill->amount_paid, 2) }}</strong> ({{ $bill->receipt_number }})
      @elseif (in_array($bill->status, ['unpaid', 'partial'], true))
        <strong>Online payment: ₱{{ number_format($bill->balance, 2) }} to pay</strong><br>
        <a href="{{ route('pay.show', $bill->pay_token) }}">Pay Online Now</a>
      @endif
    </div>
  @endif

  <div class="guest-links">
    <a href="{{ route('guest.lookup') }}">Check your booking</a> · <a href="{{ route('guest.home') }}">Back to Home</a><br>
    Want to see all your visits online? <a href="{{ rtrim(config('app.url'), '/') }}/register">Create an account</a> with the same email later.
  </div>
@endsection
