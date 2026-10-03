@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | My Waivers')
@section('body_class', 'history-page')
@section('footer', '© 2026 FMH Animal Clinic')
@section('content')
<main class="history-container">
  <div class="history-heading" style="text-align: left;">
    <h1>Waivers &amp; Consent Forms</h1>
    <p>Forms prepared by the clinic for your pets. Read them carefully, then sign online or at the clinic.</p>
  </div>

  @if ($waivers->isEmpty())
    <div class="history-empty">
      <div class="history-empty-icon">📝</div>
      <h2>No Forms Yet</h2>
      <p>When the clinic prepares a waiver or consent form for your pet, it will appear here.</p>
    </div>
  @else
    <div class="history-list">
      @foreach ($waivers as $waiver)
        <div class="history-card">
          <div class="history-card-header">
            <div class="history-pet">
              <div class="history-pet-icon">{{ $waiver->pet?->icon }}</div>
              <div>
                <h2>{{ $waiver->template?->title }}</h2>
                <p>{{ $waiver->pet?->name }} · {{ $waiver->reference }}</p>
              </div>
            </div>
            @include('partials.waiver-status', ['status' => $waiver->status])
          </div>
          <div class="history-details">
            <p><strong>Prepared:</strong> {{ $waiver->created_at->format('F j, Y') }}</p>
            <p><strong>Signed:</strong> {{ $waiver->signed_at ? $waiver->signed_at->format('F j, Y') . ' by ' . $waiver->signer_name : 'Not yet' }}</p>
          </div>
          <div class="pet-actions">
            <a href="{{ route('portal.waivers.show', $waiver) }}">{{ $waiver->isPending() ? 'Read & Sign →' : 'View →' }}</a>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</main>
@endsection
