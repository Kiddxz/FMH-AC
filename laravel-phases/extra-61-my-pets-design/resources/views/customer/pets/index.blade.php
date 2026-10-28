@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | My Pets')
@section('body_class', 'dashboard-page')
@section('content')
<style>
  /* Minimal pet cards: white card, plain text, two simple links */
  .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }
  .mp-card { display: flex; flex-direction: column; padding: 20px 22px; border: 1px solid #ece7e0; border-radius: 12px; background: #fff; }
  .mp-card.inactive { opacity: 0.6; }
  .mp-top { display: flex; align-items: center; gap: 12px; }
  .mp-icon { flex-shrink: 0; color: #e89427; font-size: 26px; line-height: 1; }
  .mp-top h2 { margin: 0; color: #26364a; font-size: 19px; font-weight: 700; overflow-wrap: anywhere; }
  .mp-top p { margin: 2px 0 0; color: #8a8f98; font-size: 14px; }
  .mp-status { margin-left: auto; color: #8a8f98; font-size: 13px; }
  .mp-meta { margin: 14px 0 0; color: #475569; font-size: 14px; }
  .mp-note { margin: 6px 0 0; color: #8a8f98; font-size: 13.5px; }
  .mp-note.due { color: #b03a37; }
  .mp-links { display: flex; gap: 20px; margin-top: auto; padding-top: 16px; }
  .mp-links a { padding: 0 !important; border: none !important; background: none !important; color: #26364a; font-size: 14px; font-weight: 600; text-decoration: none !important; }
  .mp-links a.book { color: #c46f0c; }
  .mp-links a:hover { text-decoration: underline !important; }
  .mp-card .mp-space { flex: 1; min-height: 6px; }
</style>
<main class="pets-container">
  <div class="page-heading pet-heading">
    <div>
      <h1>My Pets</h1>
      <p>Manage your registered pets.</p>
    </div>
    <a href="{{ route('portal.pets.create') }}" class="primary-btn">+ Add New Pet</a>
  </div>
  @if ($pets->isEmpty())
    <div class="no-history">
      <div class="no-history-icon">🐾</div>
      <h2>No Pets Yet</h2>
      <p>You have not registered a pet. Click "+ Add New Pet" to add your first pet.</p>
    </div>
  @else
    <div class="mp-grid">
      @foreach ($pets as $pet)
        @php
          $next = $pet->appointments->first();
          // the next dose: for each vaccine take the latest shot, then the one due soonest
          $vaccine = $pet->vaccinations->groupBy('vaccine_name')
            ->map(fn ($shots) => $shots->sortByDesc('date_given')->first())
            ->filter(fn ($shot) => $shot->next_due_date)
            ->sortBy('next_due_date')
            ->first();
        @endphp
        <article class="mp-card {{ $pet->status === 'active' ? '' : 'inactive' }}">
          <div class="mp-top">
            <span class="mp-icon" aria-hidden="true">{{ $pet->icon }}</span>
            <div>
              <h2>{{ $pet->name }}</h2>
              <p>{{ ucfirst($pet->species) }}{{ $pet->breed ? ' · ' . $pet->breed : '' }}</p>
            </div>
            @if ($pet->status !== 'active')
              <span class="mp-status">{{ ucfirst($pet->status) }}</span>
            @endif
          </div>
          <p class="mp-meta">{{ ucfirst($pet->gender) }} · {{ $pet->age_text }}</p>
          @if ($next)
            <p class="mp-note">Next visit: {{ $next->appointment_date->format('M j, Y') }}, {{ \Illuminate\Support\Carbon::parse($next->appointment_time)->format('g:i A') }}</p>
          @endif
          @if ($vaccine)
            <p class="mp-note {{ $vaccine->next_due_date->isPast() ? 'due' : '' }}">{{ $vaccine->vaccine_name }} {{ $vaccine->next_due_date->isPast() ? 'was due' : 'due' }} {{ $vaccine->next_due_date->format('M j, Y') }}</p>
          @endif
          <div class="mp-space"></div>
          <div class="mp-links">
            <a href="{{ route('portal.pets.show', $pet) }}">View Profile</a>
            @if ($pet->status === 'active')
              <a href="{{ route('portal.appointments.create', ['pet' => $pet->id]) }}" class="book">Book Appointment</a>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @endif
</main>
@endsection
