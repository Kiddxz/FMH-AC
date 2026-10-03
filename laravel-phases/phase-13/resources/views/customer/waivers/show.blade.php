@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | ' . $waiver->reference)
@section('body_class', 'dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic')
@section('content')
<main class="form-page">
  <div class="page-heading pet-heading">
    <div>
      <h1>📝 {{ $waiver->template?->title }}</h1>
      <p>{{ $waiver->reference }} · {{ $waiver->pet?->name }}</p>
    </div>
    @include('partials.waiver-status', ['status' => $waiver->status])
  </div>

  <section class="pet-form" style="margin-bottom: 25px;">
    <h2>Form</h2>
    <div style="white-space: pre-line; line-height: 1.7; border: 1px solid #eee; border-radius: 8px; padding: 18px; background: #fffdf9;">{{ $waiver->content_snapshot }}</div>
  </section>

  @can('sign', $waiver)
    <section class="pet-form">
      <h2>✍️ Sign This Form</h2>
      <form method="post" action="{{ route('portal.waivers.sign', $waiver) }}">
        @csrf
        <div class="form-group">
          <label for="signer_name">Type your full name as your signature</label>
          <input type="text" id="signer_name" name="signer_name" value="{{ old('signer_name', auth()->user()->full_name) }}" required maxlength="150">
          @error('signer_name') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
        </div>
        <div class="form-group">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
            <input type="checkbox" name="agree" value="1" style="width: auto;" required>
            I have read and I agree to this form.
          </label>
          @error('agree') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
        </div>
        <div class="form-buttons">
          <a href="{{ route('portal.waivers.index') }}" class="cancel-btn">Back</a>
          <button type="submit" class="primary-btn">Sign Form</button>
        </div>
      </form>
    </section>
  @else
    <section class="pet-form">
      <h2>Signature</h2>
      <div class="history-details" style="border-top: none; padding-top: 0;">
        <p><strong>Signed by:</strong> {{ $waiver->signer_name }}</p>
        <p><strong>Date:</strong> {{ $waiver->signed_at?->format('F j, Y g:i A') }} ({{ $waiver->signed_via === 'portal' ? 'online' : 'at the clinic' }})</p>
        <p style="color: #777;">🔒 This form is signed and can no longer be changed.</p>
      </div>
      <div class="form-buttons">
        <a href="{{ route('portal.waivers.index') }}" class="cancel-btn">Back</a>
        <a href="{{ route('portal.waivers.print', $waiver) }}" target="_blank" class="primary-btn" style="text-decoration: none;">🖨️ Print / Save as PDF</a>
      </div>
    </section>
  @endcan
</main>
@endsection
