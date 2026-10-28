@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | My Pets')
@section('body_class', 'dashboard-page')
@section('content')
@php
  $icons = [
    'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
    'syringe' => '<path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 8.7 19.3c-1 1-2.5 1-3.4 0l-.6-.6c-1-1-1-2.5 0-3.4L15 5"/><path d="m9 11 4 4"/><path d="m5 19-3 3"/><path d="m14 4 6 6"/>',
    'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
  ];
  $svg = fn (string $name, int $size = 16) => '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? '') . '</svg>';
@endphp
<style>
  .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 22px; }
  .mp-card { display: flex; flex-direction: column; overflow: hidden; border: 1px solid #eee6db; border-radius: 18px; background: #fff; box-shadow: 0 2px 10px rgba(38, 54, 74, 0.05); transition: transform 0.15s, box-shadow 0.15s; }
  .mp-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(38, 54, 74, 0.1); }
  .mp-card.inactive { opacity: 0.75; }
  .mp-head { display: flex; align-items: center; gap: 16px; padding: 22px 22px 18px; background: linear-gradient(120deg, #fffaf3 0%, #fff1df 100%); border-bottom: 1px solid #f3eee7; }
  .mp-avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 64px; height: 64px; border: 3px solid #fff; border-radius: 50%; background: #fff1df; color: #e89427; font-size: 30px; box-shadow: 0 3px 10px rgba(232, 148, 39, 0.18); }
  .mp-who { min-width: 0; }
  .mp-who h2 { margin: 0; color: #26364a; font-size: 22px; font-weight: 800; overflow-wrap: anywhere; }
  .mp-who p { margin: 3px 0 0; color: #64748b; font-size: 14px; }
  .mp-status { display: inline-block; margin-top: 6px; padding: 2px 9px; border-radius: 999px; background: #eee; color: #666; font-size: 12px; font-weight: 700; }
  .mp-body { flex: 1; padding: 16px 22px 6px; }
  .mp-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
  .mp-chip { padding: 5px 12px; border-radius: 999px; background: #f4f1ec; color: #475569; font-size: 13px; font-weight: 700; }
  .mp-chip.female { background: #fdeef3; color: #b0456b; }
  .mp-chip.male { background: #e9f1fd; color: #2f5f9e; }
  .mp-line { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-top: 1px solid #f3eee7; color: #475569; font-size: 14px; }
  .mp-line svg { flex-shrink: 0; margin-top: 2px; color: #e89427; }
  .mp-line strong { display: block; color: #26364a; font-size: 13px; }
  .mp-line .muted { color: #94a3b8; }
  .mp-line .due { color: #b03a37; font-weight: 700; }
  .mp-actions { display: flex; gap: 10px; padding: 14px 22px 20px; }
  .mp-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 10px; white-space: nowrap; box-sizing: border-box; border: 1px solid #eee6db; border-radius: 10px; background: #fff; color: #26364a; font-size: 14px; font-weight: 700; text-decoration: none; transition: background 0.15s, border-color 0.15s, color 0.15s; }
  .mp-btn:hover { border-color: #e89427; color: #c46f0c; background: #fffaf3; }
  .mp-btn.primary { border-color: #e89427; background: #e89427; color: #fff; }
  .mp-btn.primary:hover { background: #d57d13; color: #fff; }
  .mp-add { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; min-height: 280px; border: 2px dashed #f0d3ad; border-radius: 18px; background: #fffdfa; color: #c46f0c; font-size: 16px; font-weight: 700; text-decoration: none; transition: background 0.15s, border-color 0.15s; }
  .mp-add:hover { border-color: #e89427; background: #fff5e8; }
  .mp-add span { display: inline-flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 50%; background: #fff1df; }
  .mp-add small { color: #94a3b8; font-size: 13px; font-weight: 500; }
  @media (max-width: 500px) {
    .mp-grid { grid-template-columns: 1fr; }
    .mp-actions { flex-direction: column; }
    .mp-btn { flex: none; min-height: 44px; }
    .mp-add { min-height: 160px; }
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
          <div class="mp-head">
            <span class="mp-avatar" aria-hidden="true">{{ $pet->icon }}</span>
            <div class="mp-who">
              <h2>{{ $pet->name }}</h2>
              <p>{{ ucfirst($pet->species) }}{{ $pet->breed ? ' · ' . $pet->breed : '' }}</p>
              @if ($pet->status !== 'active')
                <span class="mp-status">{{ ucfirst($pet->status) }}</span>
              @endif
            </div>
          </div>
          <div class="mp-body">
            <div class="mp-chips">
              <span class="mp-chip {{ $pet->gender }}">{{ ucfirst($pet->gender) }}</span>
              <span class="mp-chip">{{ $pet->age_text }}</span>
              @if ($pet->color)<span class="mp-chip">{{ $pet->color }}</span>@endif
            </div>
            <div class="mp-line">
              {!! $svg('calendar') !!}
              <div>
                <strong>Next visit</strong>
                @if ($next)
                  {{ $next->appointment_date->format('M j, Y') }} at {{ \Illuminate\Support\Carbon::parse($next->appointment_time)->format('g:i A') }}
                  · {{ $next->service_names }}
                @else
                  <span class="muted">No upcoming appointment</span>
                @endif
              </div>
            </div>
            <div class="mp-line">
              {!! $svg('syringe') !!}
              <div>
                <strong>Next vaccine</strong>
                @if ($vaccine)
                  <span class="{{ $vaccine->next_due_date->isPast() ? 'due' : '' }}">{{ $vaccine->vaccine_name }}, {{ $vaccine->next_due_date->isPast() ? 'was due' : 'due' }} {{ $vaccine->next_due_date->format('M j, Y') }}</span>
                @else
                  <span class="muted">Nothing due</span>
                @endif
              </div>
            </div>
          </div>
          <div class="mp-actions">
            <a href="{{ route('portal.pets.show', $pet) }}" class="mp-btn">View Profile</a>
            @if ($pet->status === 'active')
              <a href="{{ route('portal.appointments.create', ['pet' => $pet->id]) }}" class="mp-btn primary">Book Appointment</a>
            @endif
          </div>
        </article>
      @endforeach
      <a href="{{ route('portal.pets.create') }}" class="mp-add">
        <span>{!! $svg('plus', 24) !!}</span>
        Add another pet
        <small>Dog, cat, bird, rabbit and more</small>
      </a>
    </div>
  @endif
</main>
@endsection
