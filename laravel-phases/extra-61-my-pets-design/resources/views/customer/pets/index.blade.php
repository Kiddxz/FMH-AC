@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | My Pets')
@section('body_class', 'dashboard-page')
@section('content')
@php
  $icons = [
    'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
    'syringe' => '<path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 8.7 19.3c-1 1-2.5 1-3.4 0l-.6-.6c-1-1-1-2.5 0-3.4L15 5"/><path d="m9 11 4 4"/><path d="m5 19-3 3"/><path d="m14 4 6 6"/>',
    'eye' => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
    'book' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="M12 14v4"/><path d="M10 16h4"/>',
  ];
  $svg = fn (string $name, int $size = 16) => '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? '') . '</svg>';
@endphp
<style>
  .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 22px; }
  .mp-card { display: flex; flex-direction: column; border: 1px solid #eee6db; border-radius: 16px; background: #fff; box-shadow: 0 1px 4px rgba(38, 54, 74, 0.05); transition: box-shadow 0.15s, border-color 0.15s; }
  .mp-card:hover { border-color: #f0d3ad; box-shadow: 0 8px 22px rgba(38, 54, 74, 0.08); }
  .mp-card.inactive { opacity: 0.7; }
  .mp-top { display: flex; align-items: center; gap: 14px; padding: 20px 20px 16px; }
  .mp-avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 56px; height: 56px; border-radius: 14px; background: #fff4e6; color: #e89427; font-size: 28px; }
  .mp-who { flex: 1; min-width: 0; }
  .mp-who h2 { margin: 0; color: #26364a; font-size: 20px; font-weight: 800; overflow-wrap: anywhere; }
  .mp-who p { margin: 2px 0 0; color: #8a8f98; font-size: 14px; }
  .mp-status { flex-shrink: 0; padding: 3px 10px; border-radius: 999px; background: #eef0f2; color: #64748b; font-size: 12px; font-weight: 700; }
  .mp-facts { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 0 20px; }
  .mp-fact { padding: 10px 12px; border-radius: 10px; background: #faf8f5; }
  .mp-fact small { display: block; color: #94a3b8; font-size: 11.5px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
  .mp-fact span { display: block; margin-top: 2px; color: #26364a; font-size: 14px; font-weight: 600; }
  .mp-info { flex: 1; padding: 12px 20px 16px; }
  .mp-line { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; color: #475569; font-size: 14px; line-height: 1.4; }
  .mp-line svg { flex-shrink: 0; margin-top: 2px; color: #c9a57a; }
  .mp-line b { color: #26364a; font-weight: 600; }
  .mp-line .muted { color: #a0a7b1; }
  .mp-line .due { color: #b03a37; font-weight: 600; }
  .mp-foot { display: flex; border-top: 1px solid #f1ebe3; }
  .mp-foot a { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 14px 6px; color: #64748b; font-size: 14px; font-weight: 600; white-space: nowrap; text-decoration: none !important; border-bottom: none !important; transition: background 0.15s, color 0.15s; }
  .mp-foot a + a { border-left: 1px solid #f1ebe3; }
  .mp-foot a:hover { background: #fffaf3; color: #c46f0c; }
  .mp-foot a.book { color: #c46f0c; }
  .mp-foot a:first-child { border-bottom-left-radius: 16px; }
  .mp-foot a:last-child { border-bottom-right-radius: 16px; }
  @media (max-width: 500px) {
    .mp-grid { grid-template-columns: 1fr; }
  }
</style>
<main class="pets-container">
  <div class="page-heading pet-heading">
    <div>
      <h1>My Pets</h1>
      <p>{{ $pets->count() ? 'You have ' . $pets->count() . ' registered ' . \Illuminate\Support\Str::plural('pet', $pets->count()) . '.' : 'Manage your registered pets.' }}</p>
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
            <span class="mp-avatar" aria-hidden="true">{{ $pet->icon }}</span>
            <div class="mp-who">
              <h2>{{ $pet->name }}</h2>
              <p>{{ ucfirst($pet->species) }}{{ $pet->breed ? ' · ' . $pet->breed : '' }}</p>
            </div>
            @if ($pet->status !== 'active')
              <span class="mp-status">{{ ucfirst($pet->status) }}</span>
            @endif
          </div>
          <div class="mp-facts">
            <div class="mp-fact"><small>Sex</small><span>{{ ucfirst($pet->gender) }}</span></div>
            <div class="mp-fact"><small>Age</small><span>{{ \Illuminate\Support\Str::replaceLast(' old', '', $pet->age_text) }}</span></div>
          </div>
          <div class="mp-info">
            <div class="mp-line">
              {!! $svg('calendar') !!}
              @if ($next)
                <span><b>Next visit:</b> {{ $next->appointment_date->format('M j, Y') }}, {{ \Illuminate\Support\Carbon::parse($next->appointment_time)->format('g:i A') }}</span>
              @else
                <span class="muted">No upcoming visit</span>
              @endif
            </div>
            <div class="mp-line">
              {!! $svg('syringe') !!}
              @if ($vaccine)
                <span class="{{ $vaccine->next_due_date->isPast() ? 'due' : '' }}"><b>{{ $vaccine->vaccine_name }}:</b> {{ $vaccine->next_due_date->isPast() ? 'was due' : 'due' }} {{ $vaccine->next_due_date->format('M j, Y') }}</span>
              @else
                <span class="muted">No vaccine due</span>
              @endif
            </div>
          </div>
          <div class="mp-foot">
            <a href="{{ route('portal.pets.show', $pet) }}">{!! $svg('eye') !!} View Profile</a>
            @if ($pet->status === 'active')
              <a href="{{ route('portal.appointments.create', ['pet' => $pet->id]) }}" class="book">{!! $svg('book') !!} Book Appointment</a>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @endif
</main>
@endsection
