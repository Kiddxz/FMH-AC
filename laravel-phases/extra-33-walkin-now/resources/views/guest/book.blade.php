@extends('guest.layout')
@section('title', 'Walk-in Booking')
@section('content')
  <h1>Walk-in Booking</h1>
  <p class="account-description">For pet owners at the clinic now. Enter your information and your pet's: today's date and the time you book are saved automatically, and you get a queue number.</p>
  @include('partials.alerts')
  @unless ($openNow)
    <div style="margin: 0 0 18px; padding: 14px 18px; border: 1px solid #f3c2c0; border-radius: 12px; background: #fdecec; color: #8a2b27; line-height: 1.5;">
      <strong>The clinic is closed right now.</strong>
      {{ $todayText ? 'Today\'s hours: ' . $todayText . '.' : 'The clinic is closed today.' }}
      Walk-in bookings can only be made during clinic hours.
    </div>
  @endunless

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

    <h2>Visit</h2>
    <div class="form-group">
      <label>Services</label>
      @include('partials.appointments.service-picker', ['services' => $services, 'selected' => old('service_ids', $selected)])
    </div>
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; margin: 4px 0 16px; padding: 12px 16px; border-radius: 12px; background: #f6f1ea; color: #26364a;">
      <span style="color: #6b7280;">Date and time of visit</span>
      <strong>Today, {{ now()->format('F j, Y') }} · saved when you book</strong>
    </div>
    <div class="form-group">
      <label for="reason">Reason / Notes (optional)</label>
      <textarea id="reason" name="reason" rows="3" placeholder="Tell us about your pet's concern">{{ old('reason') }}</textarea>
    </div>

    <h2>Payment</h2>
    <div class="pay-options">
      <label class="pay-option">
        <input type="radio" name="payment_method" value="clinic" {{ old('payment_method', 'clinic') === 'clinic' ? 'checked' : '' }}>
        <span><strong>Pay at the clinic</strong><small>Cash, GCash, Maya or card at the counter.</small></span>
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
    <button class="regisbtn" type="submit" @disabled(! $openNow)>Book Walk-in Visit</button>
    <p class="guest-note" style="text-align: center;">After booking you get a <strong>queue number</strong>. Please wait to be called.</p>
  </form>

  <div class="guest-links">
    Already booked? <a href="{{ route('guest.lookup') }}">Check your booking</a><br>
    Registered Client? <a href="{{ rtrim(config('app.url'), '/') }}/login?role=owner">Log in</a> · Want an account? <a href="{{ rtrim(config('app.url'), '/') }}/register">Become a Registered Client</a>
  </div>
@endsection
