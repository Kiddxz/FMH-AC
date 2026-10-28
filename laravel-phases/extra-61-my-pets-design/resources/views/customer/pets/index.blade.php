@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | My Pets')
@section('body_class', 'dashboard-page')
@section('content')
<style>
  /* Pet cards: the pet's details, then two soft buttons (View Profile / Book) */
  .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 20px; }
  .mp-card { display: flex; flex-direction: column; padding: 22px; border: 1px solid #ece6de; border-radius: 16px; background: #fff; box-shadow: 0 1px 2px rgba(38, 54, 74, 0.04); transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s; }
  .mp-card:hover { border-color: #f1d7b6; box-shadow: 0 10px 26px rgba(38, 54, 74, 0.07); transform: translateY(-2px); }
  .mp-card.inactive { opacity: 0.6; }
  .mp-top { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
  .mp-icon { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 52px; height: 52px; border-radius: 50%; background: #fff6ec; color: #e89427; font-size: 26px; }
  .mp-top h2 { margin: 0; color: #26364a; font-size: 19px; font-weight: 700; overflow-wrap: anywhere; }
  .mp-status { margin-left: auto; padding: 2px 10px; border: 1px solid #e5e7eb; border-radius: 999px; color: #8a8f98; font-size: 12px; }
  .mp-details { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px; flex: 1; margin: 0 0 20px; }
  .mp-details div { min-width: 0; }
  .mp-details dt { color: #9aa1ab; font-size: 12px; font-weight: 600; letter-spacing: 0.03em; text-transform: uppercase; }
  .mp-details dd { margin: 3px 0 0; color: #26364a; font-size: 14.5px; font-weight: 600; overflow-wrap: anywhere; }
  .mp-buttons { display: flex; gap: 10px; }
  .mp-buttons a { flex: 1; display: inline-flex; align-items: center; justify-content: center; height: 40px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none !important; white-space: nowrap; transition: background 0.15s, color 0.15s; }
  .mp-buttons .view { background: #f4f1ec; color: #334155; }
  .mp-buttons .view:hover { background: #ebe6df; color: #26364a; }
  .mp-buttons .book { background: #fff1df; color: #c46f0c; }
  .mp-buttons .book:hover { background: #ffe5c4; color: #a95f08; }
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
        <article class="mp-card {{ $pet->status === 'active' ? '' : 'inactive' }}">
          <div class="mp-top">
            <span class="mp-icon" aria-hidden="true">{{ $pet->icon }}</span>
            <h2>{{ $pet->name }}</h2>
            @if ($pet->status !== 'active')
              <span class="mp-status">{{ ucfirst($pet->status) }}</span>
            @endif
          </div>
          <dl class="mp-details">
            <div><dt>Species</dt><dd>{{ ucfirst($pet->species) }}</dd></div>
            <div><dt>Breed</dt><dd>{{ $pet->breed ?: '—' }}</dd></div>
            <div><dt>Sex</dt><dd>{{ ucfirst($pet->gender) }}</dd></div>
            <div><dt>Age</dt><dd>{{ \Illuminate\Support\Str::replaceLast(' old', '', $pet->age_text) }}</dd></div>
          </dl>
          <div class="mp-buttons">
            <a href="{{ route('portal.pets.show', $pet) }}" class="view">View Profile</a>
            @if ($pet->status === 'active')
              <a href="{{ route('portal.appointments.create', ['pet' => $pet->id]) }}" class="book">Book</a>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @endif
</main>
@endsection
