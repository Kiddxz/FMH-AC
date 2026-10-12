@extends('guest.layout')
@section('title', 'Book a Visit')
@php
  $mainSite = rtrim(config('app.url'), '/');
  $svg = fn (string $paths) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
  $check = $svg('<path d="M20 6 9 17l-5-5"/>');
  $calendar = $svg('<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>');
  $search = $svg('<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>');
  $clock = $svg('<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>');
@endphp
@section('wide')
  <main class="wk">
    <section class="wk-hero">
      <div>
        <span class="wk-pill">No account needed</span>
        <h1>Book your pet's visit <span>in minutes</span></h1>
        <p>Just enter your information and your pet's, choose the services and a free time slot. The clinic will confirm your booking.</p>
        <div class="wk-buttons">
          <a href="{{ route('guest.book') }}" class="wk-btn primary">{!! $calendar !!} Book a Visit</a>
          <a href="{{ route('guest.lookup') }}" class="wk-btn outline">{!! $search !!} Check My Booking</a>
        </div>
        <div class="wk-trust">
          <span>{!! $check !!} Free to book</span>
          <span>{!! $check !!} {{ $onlinePayment ? 'Pay at the clinic or online (GCash, Maya, Card)' : 'Pay at the clinic' }}</span>
          <span>{!! $check !!} Cancel anytime before your visit</span>
        </div>
      </div>

      <aside class="wk-card">
        <h3>{!! $clock !!} Clinic Hours</h3>
        <div class="wk-today">
          <div><small style="color: #8a8f98;">Today, {{ now()->format('l') }}</small><br><strong>{{ $todayText ?? 'Closed' }}</strong></div>
          <span class="wk-badge {{ $todayText ? 'open' : 'closed' }}">{{ $todayText ? 'Open today' : 'Closed today' }}</span>
        </div>
        <div class="wk-hours">
          @foreach ($hours as $row)
            <div><span>{{ $row['from'] === $row['to'] ? $row['from'] : $row['from'] . ' – ' . $row['to'] }}</span><span>{{ $row['text'] }}</span></div>
          @endforeach
        </div>
        <div class="wk-call">
          Questions? Call
          @foreach (config('clinic.inquiries', []) as $number)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $number) }}">{{ $number }}</a>@if (! $loop->last) or @endif
          @endforeach
        </div>
      </aside>
    </section>

    <section class="wk-section">
      <h2>How It Works</h2>
      <p>Three easy steps. No sign-up, no password.</p>
      <div class="wk-steps">
        <div class="wk-step"><b>1</b><strong>Enter your details</strong><span>Your name, mobile number, and your pet's name, species and age.</span></div>
        <div class="wk-step"><b>2</b><strong>Pick services and a schedule</strong><span>Only the free time slots are shown, so there is no double booking.</span></div>
        <div class="wk-step"><b>3</b><strong>Keep your reference number</strong><span>Use it with your mobile number to check, pay or cancel your booking.</span></div>
      </div>
    </section>

    <section class="wk-section">
      <h2>Services You Can Book</h2>
      <p>Tap a service to start your booking with it already chosen.</p>
      <div class="wk-services">
        @foreach ($services as $service)
          <a href="{{ route('guest.book', ['service' => $service->id]) }}" class="wk-service">
            <strong>{{ $service->name }}</strong>
            <small>About {{ $service->duration_minutes }} minutes</small>
            <span class="price">₱{{ number_format($service->price, 2) }}</span>
            <span class="go">Book this &rarr;</span>
          </a>
        @endforeach
      </div>
    </section>

    <section class="wk-foot">
      <p>Want to keep your pet's records and visit history online?</p>
      <div style="display: flex; flex-wrap: wrap; gap: 10px;">
        <a href="{{ $mainSite }}/register">Become a Registered Client</a>
        <a href="{{ $mainSite }}/login?role=owner" class="ghost">Log in</a>
      </div>
    </section>
  </main>
@endsection
