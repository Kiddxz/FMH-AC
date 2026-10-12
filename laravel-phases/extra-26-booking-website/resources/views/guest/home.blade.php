@extends('guest.layout')
@section('title', 'Online Booking')
@section('content')
  @php($mainSite = rtrim(config('app.url'), '/'))
  <div class="bk-hero">
    <h1>Book a Visit for Your Pet</h1>
    <p class="account-description">No account needed. Book in a few minutes, and the clinic will confirm your appointment.</p>
  </div>

  <div class="bk-actions">
    <a href="{{ route('guest.book') }}" class="bk-action primary">
      <strong>Book an Appointment</strong>
      <small>Choose the services, the date and the time.{{ $onlinePayment ? ' Pay at the clinic or online.' : '' }}</small>
    </a>
    <a href="{{ route('guest.lookup') }}" class="bk-action">
      <strong>Check or Cancel My Booking</strong>
      <small>Use your reference number (APP-000123) and your mobile number.</small>
    </a>
  </div>

  <h2>How It Works</h2>
  <div class="bk-steps">
    <div><b>1. Fill in the form</b>Your name, mobile number and your pet.</div>
    <div><b>2. Pick a schedule</b>Only the free time slots are shown.</div>
    <div><b>3. Get your reference</b>Keep it to check or cancel later.</div>
  </div>

  <h2>Services You Can Book Online</h2>
  <div class="bk-chips">
    @foreach ($services as $service)
      <span>{{ $service->name }} · ₱{{ number_format($service->price, 2) }}</span>
    @endforeach
  </div>

  <h2>Clinic Hours</h2>
  <div class="guest-summary" style="margin-top: 0;">
    @foreach ($hours as $row)
      <div><span>{{ $row['from'] === $row['to'] ? $row['from'] : $row['from'] . ' – ' . $row['to'] }}</span><span>{{ $row['text'] }}</span></div>
    @endforeach
  </div>

  <div class="guest-links">
    For other services or questions, call {{ implode(' or ', config('clinic.inquiries', [])) }}.<br>
    Have an account? <a href="{{ $mainSite }}/login?role=owner">Log in on the main website</a>
  </div>
@endsection
