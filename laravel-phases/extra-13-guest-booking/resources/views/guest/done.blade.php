@extends('guest.layout')
@section('title', 'Booking Received')
@section('content')
  <h1>Booking Received!</h1>
  <p class="account-description">Thank you, {{ $appointment->customer->first_name }}. The clinic will confirm your booking.</p>

  <p style="text-align: center; color: #555;">Your reference number:</p>
  <div class="guest-ref">{{ $appointment->reference }}</div>
  <p class="guest-note" style="text-align: center;">Take a screenshot or write it down. You need it and your mobile number to check or cancel this booking.</p>

  <div class="guest-summary">
    <div><span>Pet</span><span>{{ $appointment->pet->name }}</span></div>
    <div><span>Services</span><span>{{ $appointment->service_names }}</span></div>
    <div><span>Date</span><span>{{ $appointment->appointment_date->format('l, F j, Y') }}</span></div>
    <div><span>Time</span><span>{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</span></div>
    <div><span>Estimated total</span><span>₱{{ number_format($appointment->total_price, 2) }} (pay at the clinic)</span></div>
    <div><span>Status</span><span>{{ ucfirst($appointment->status) }}</span></div>
  </div>

  <div class="guest-links">
    <a href="{{ route('guest.lookup') }}">Check or cancel your booking</a> · <a href="{{ route('home') }}">Back to Home</a><br>
    Want to see all your visits online? <a href="{{ route('register') }}">Create an account</a> with the same email later.
  </div>
@endsection
