@extends('guest.layout')
@section('title', 'Check Your Booking')
@section('content')
  <h1>Check Your Booking</h1>
  <p class="account-description">For bookings made without an account. Enter your reference number and mobile number.</p>
  @include('partials.alerts')

  <form action="{{ route('guest.lookup.check') }}" method="post" style="max-width: 460px; margin: 0 auto;">
    @csrf
    <div class="form-group">
      <label for="reference">Reference Number</label>
      <input type="text" id="reference" name="reference" value="{{ old('reference') }}" placeholder="APP-000123" required style="text-transform: uppercase;">
    </div>
    <div class="form-group">
      <label for="lookup_mobile">Mobile Number</label>
      <input type="tel" id="lookup_mobile" name="contact_number" value="{{ old('contact_number') }}" placeholder="09XXXXXXXXX" required>
    </div>
    <button class="regisbtn" type="submit">Find My Booking</button>
  </form>

  <div class="guest-links">
    <a href="{{ route('guest.book') }}">Make a new booking</a> · <a href="{{ route('home') }}">Back to Home</a>
  </div>
@endsection
