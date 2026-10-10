@extends('guest.layout')
@section('title', 'Book Without an Account')
@section('content')
  <h1>Book an Appointment</h1>
  <p class="account-description">No account needed. Fill in the form and the clinic will confirm your booking.</p>
  @include('partials.alerts')

  <form action="{{ route('guest.book.store') }}" method="post">
    @csrf
    {{-- robots fill in every field; people never see this one --}}
    <div style="position: absolute; left: -9999px;" aria-hidden="true">
      <label for="website">Leave this empty</label>
      <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    <h2>Your Information</h2>
    <div class="guest-grid">
      <div class="form-group">
        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required>
      </div>
      <div class="form-group">
        <label for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required>
      </div>
      <div class="form-group">
        <label for="contact_number">Mobile Number</label>
        <input type="tel" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" placeholder="09XXXXXXXXX" autocomplete="tel" required>
      </div>
      <div class="form-group">
        <label for="email">Email (optional)</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="For your records" autocomplete="email">
      </div>
    </div>

    <h2>Your Pet</h2>
    <div class="guest-grid">
      <div class="form-group">
        <label for="pet_name">Pet Name</label>
        <input type="text" id="pet_name" name="pet_name" value="{{ old('pet_name') }}" required>
      </div>
      <div class="form-group">
        <label for="species">Species</label>
        <select id="species" name="species" required>
          <option value="">Choose</option>
          @foreach ($species as $kind)
            <option value="{{ $kind }}" @selected(old('species') === $kind)>{{ ucfirst($kind) }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label for="breed">Breed (optional)</label>
        <input type="text" id="breed" name="breed" value="{{ old('breed') }}">
      </div>
      <div class="form-group">
        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
          <option value="">Choose</option>
          <option value="male" @selected(old('gender') === 'male')>Male</option>
          <option value="female" @selected(old('gender') === 'female')>Female</option>
        </select>
      </div>
      <div class="form-group">
        <label for="age">Age (years)</label>
        <input type="number" id="age" name="age" value="{{ old('age') }}" min="0" max="50" required>
      </div>
    </div>

    <h2>Appointment</h2>
    <div class="form-group">
      <label>Services</label>
      @include('partials.appointments.service-picker', ['services' => $services, 'selected' => old('service_ids', $selected)])
    </div>
    <div class="guest-grid">
      @include('partials.appointments.slot-picker', [
        'date' => old('appointment_date'),
        'time' => old('appointment_time'),
        'slotsUrl' => route('guest.book.slots'),
      ])
    </div>
    <div class="form-group">
      <label for="reason">Reason / Notes (optional)</label>
      <textarea id="reason" name="reason" rows="3" placeholder="Tell us about your pet's concern">{{ old('reason') }}</textarea>
    </div>

    <h2>Payment</h2>
    <div class="pay-options">
      <label class="pay-option">
        <input type="radio" name="payment_method" value="clinic" {{ old('payment_method', 'clinic') === 'clinic' ? 'checked' : '' }}>
        <span><strong>Pay at the clinic</strong><small>Cash, GCash, Maya or card on the day of your visit.</small></span>
      </label>
      <label class="pay-option {{ $onlinePayment ? '' : 'disabled' }}">
        <input type="radio" name="payment_method" value="online" {{ old('payment_method') === 'online' ? 'checked' : '' }} @disabled(! $onlinePayment)>
        <span><strong>Pay online now</strong><small>{{ $onlinePayment ? 'GCash, Maya or card on the secure PayMongo page, right after booking.' : 'Not available right now. Please pay at the clinic.' }}</small></span>
      </label>
    </div>
    <p class="guest-note">The amount is the price of the services you chose. If the veterinarian adds other services or medicines, you pay those at the clinic.</p>

    <div class="tnc">
      <label>
        <input type="checkbox" name="privacy" value="1" {{ old('privacy') ? 'checked' : '' }} required>
        <span>I allow FMH Animal Clinic to keep my information and my pet's information for this booking and future visits.</span>
      </label>
    </div>
    <button class="regisbtn" type="submit">Book Appointment</button>
    <p class="guest-note" style="text-align: center;">Your booking will be <strong>Pending</strong> until the clinic confirms it.</p>
  </form>

  <div class="guest-links">
    Already booked? <a href="{{ route('guest.lookup') }}">Check or cancel your booking</a><br>
    Have an account? <a href="{{ route('login') }}?role=owner">Log in</a> · Want one? <a href="{{ route('register') }}">Create Account</a>
  </div>
@endsection
