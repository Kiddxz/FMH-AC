@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | My Waivers')
@section('body_class', 'history-page')
@section('footer', '© 2026 FMH Animal Clinic')
@section('content')
<style>
  /* One row per form, each in its own box (same look as the Appointment History) */
  .appt-list { display: grid; gap: 12px; margin-top: 28px; }
  .appt-head, .appt-row { display: grid; grid-template-columns: 2fr 0.9fr 1fr 1fr 1.6fr 1.1fr 1fr; gap: 14px; align-items: center; }
  .appt-head { padding: 0 22px; color: #8a8f98; font-size: 12.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
  .appt-row { padding: 16px 22px; border: 1px solid #eee6db; border-radius: 14px; background: #fff; box-shadow: 0 3px 10px rgba(38, 54, 74, 0.05); transition: border-color 0.2s, box-shadow 0.2s; }
  .appt-row:hover { border-color: #e89427; box-shadow: 0 8px 20px rgba(232, 148, 39, 0.12); }
  .appt-pet { display: flex; align-items: center; gap: 12px; min-width: 0; }
  .appt-pet-icon { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 44px; height: 44px; border-radius: 12px; background: #fff1df; color: #e89427; font-size: 20px; }
  .appt-pet strong { display: block; color: #26364a; font-size: 16px; line-height: 1.3; }
  .appt-cell { min-width: 0; color: #374151; font-size: 14.5px; overflow-wrap: anywhere; }
  .appt-cell small { display: block; color: #8a8f98; font-size: 13px; }
  .appt-go { text-align: right; }
  .appt-go a { display: inline-block; padding: 9px 14px; border-radius: 10px; background: #fff1df; color: #c46f0c; font-size: 14px; font-weight: 700; text-decoration: none !important; border: none !important; white-space: nowrap; }
  .appt-go a:hover { background: #ffe6c7; color: #b5630a !important; }
  /* smaller screens: each box shows its details one under the other */
  @media (max-width: 1000px) {
    .appt-head { display: none; }
    .appt-row { grid-template-columns: 1fr 1fr; gap: 10px 16px; }
    .appt-pet { grid-column: 1 / -1; }
    .appt-wide { grid-column: 1 / -1; }
    .appt-cell::before { content: attr(data-label); display: block; margin-bottom: 2px; color: #8a8f98; font-size: 11.5px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
    .appt-go { grid-column: 1 / -1; text-align: left; }
  }
</style>
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
    <div class="appt-list">
      <div class="appt-head" aria-hidden="true">
        <span>Form</span><span>Pet</span><span>Reference</span><span>Prepared</span><span>Signed</span><span>Status</span><span></span>
      </div>
      @foreach ($waivers as $waiver)
        <div class="appt-row">
          <div class="appt-pet">
            <span class="appt-pet-icon">{{ $waiver->pet?->icon }}</span>
            <strong>{{ $waiver->template?->title }}</strong>
          </div>
          <div class="appt-cell" data-label="Pet">{{ $waiver->pet?->name }}</div>
          <div class="appt-cell" data-label="Reference">{{ $waiver->reference }}</div>
          <div class="appt-cell" data-label="Prepared">{{ $waiver->created_at->format('M j, Y') }}</div>
          <div class="appt-cell appt-wide" data-label="Signed">
            @if ($waiver->signed_at)
              {{ $waiver->signed_at->format('M j, Y') }}<small>by {{ $waiver->signer_name }}</small>
            @else
              Not yet
            @endif
          </div>
          <div class="appt-cell" data-label="Status">@include('partials.waiver-status', ['status' => $waiver->status])</div>
          <div class="appt-go">
            <a href="{{ route('portal.waivers.show', $waiver) }}">{{ $waiver->isPending() ? 'Read & Sign' : 'View Details' }}</a>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</main>
@endsection
