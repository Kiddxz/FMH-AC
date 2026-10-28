@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | My Pets')
@section('body_class', 'dashboard-page')
@section('content')
<style>
  /* Pet cards: white, quiet colors, details in simple rows, two links at the bottom */
  .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 20px; }
  .mp-card { display: flex; flex-direction: column; border: 1px solid #ece6de; border-radius: 14px; background: #fff; box-shadow: 0 1px 2px rgba(38, 54, 74, 0.04); transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s; }
  .mp-card:hover { border-color: #f1d7b6; box-shadow: 0 10px 26px rgba(38, 54, 74, 0.07); transform: translateY(-2px); }
  .mp-card.inactive { opacity: 0.6; }
  .mp-top { display: flex; align-items: center; gap: 14px; padding: 22px 22px 0; }
  .mp-icon { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 48px; height: 48px; border-radius: 50%; background: #fff6ec; color: #e89427; font-size: 24px; }
  .mp-name { min-width: 0; }
  .mp-name h2 { margin: 0; color: #26364a; font-size: 18px; font-weight: 700; overflow-wrap: anywhere; }
  .mp-name p { margin: 3px 0 0; color: #8a8f98; font-size: 13.5px; }
  .mp-status { margin-left: auto; padding: 2px 10px; border: 1px solid #e5e7eb; border-radius: 999px; color: #8a8f98; font-size: 12px; }
  .mp-rows { flex: 1; margin: 16px 22px 0; }
  .mp-row { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-top: 1px solid #f4f0ea; font-size: 13.5px; }
  .mp-row span:first-child { color: #9aa1ab; }
  .mp-row span:last-child { color: #334155; font-weight: 600; text-align: right; }
  .mp-row .none { color: #b6bcc5 !important; font-weight: 400 !important; }
  .mp-row .due { color: #b03a37 !important; }
  .mp-links { display: flex; justify-content: space-between; margin-top: 8px; padding: 14px 22px 16px; border-top: 1px solid #f4f0ea; }
  .mp-links a { padding: 0 !important; border: none !important; background: none !important; color: #475569; font-size: 14px; font-weight: 600; text-decoration: none !important; transition: color 0.15s; }
  .mp-links a:hover { color: #26364a; }
  .mp-links a.book { color: #d07a12; }
  .mp-links a.book:hover { color: #a95f08; }
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
            <div class="mp-name">
              <h2>{{ $pet->name }}</h2>
              <p>{{ ucfirst($pet->species) }}{{ $pet->breed ? ' · ' . $pet->breed : '' }}</p>
            </div>
            @if ($pet->status !== 'active')
              <span class="mp-status">{{ ucfirst($pet->status) }}</span>
            @endif
          </div>
          <div class="mp-rows">
            <div class="mp-row"><span>Sex</span><span>{{ ucfirst($pet->gender) }}</span></div>
            <div class="mp-row"><span>Age</span><span>{{ \Illuminate\Support\Str::replaceLast(' old', '', $pet->age_text) }}</span></div>
            <div class="mp-row"><span>Next visit</span>
              @if ($next)
                <span>{{ $next->appointment_date->format('M j') }}, {{ \Illuminate\Support\Carbon::parse($next->appointment_time)->format('g:i A') }}</span>
              @else
                <span class="none">None</span>
              @endif
            </div>
            <div class="mp-row"><span>Vaccine due</span>
              @if ($vaccine)
                <span class="{{ $vaccine->next_due_date->isPast() ? 'due' : '' }}">{{ $vaccine->next_due_date->format('M j, Y') }}</span>
              @else
                <span class="none">None</span>
              @endif
            </div>
          </div>
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
