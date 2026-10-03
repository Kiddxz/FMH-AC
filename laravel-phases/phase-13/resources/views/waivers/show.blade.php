{{-- One waiver: the locked text, signature (at the clinic), review (vet) and print --}}
@php
  $p = $area === 'superadmin' ? 'superadmin' : 'admin';
  $staffCanSign = $area === 'staff' && auth()->user()->can('sign', $waiver);
  $vetCanReview = $area === 'admin' && auth()->user()->can('review', $waiver);
@endphp
@extends('layouts.' . $area)
@section('title', 'FMH Animal Clinic | ' . $waiver->reference)
@section('body_class', ['staff' => 'admin-layout', 'admin' => 'admin-dashboard-page', 'superadmin' => 'superadmin-page'][$area])
@section('footer', '© 2026 FMH Animal Clinic | ' . ['staff' => 'Staff', 'admin' => 'Admin', 'superadmin' => 'Super Admin'][$area] . ' Panel')
@section('content')
<main class="{{ $p }}-container">
  <div class="{{ $p }}-page-heading">
    <div>
      <h1>{{ $waiver->reference }} · {{ $waiver->template?->title }}</h1>
      <p>{{ $waiver->customer?->full_name }} · {{ $waiver->pet?->name }} · @include('partials.waiver-status', ['status' => $waiver->status])</p>
    </div>
  </div>

  <div class="{{ $p }}-table-card" style="padding: 25px;">
    <h2 style="margin-bottom: 12px;">Form</h2>
    <div style="white-space: pre-line; line-height: 1.7; border: 1px solid #eee; border-radius: 8px; padding: 18px; background: #fffdf9;">{{ $waiver->content_snapshot }}</div>
    @if (! $waiver->isPending())
      <p style="margin-top: 10px; color: #777; font-size: 13px;">🔒 This text is locked because the form was signed.</p>
    @endif
  </div>

  <div class="{{ $p }}-table-card" style="padding: 25px;">
    <h2 style="margin-bottom: 12px;">Signature &amp; Review</h2>
    <table class="{{ $p }}-table">
      <tbody>
        <tr><th>Prepared by</th><td>{{ $waiver->preparer?->full_name ?? '—' }} · {{ $waiver->created_at->format('F j, Y g:i A') }}</td></tr>
        <tr><th>Signed by</th><td>{{ $waiver->signer_name ? $waiver->signer_name . ' · ' . $waiver->signed_at->format('F j, Y g:i A') . ' · ' . ($waiver->signed_via === 'portal' ? 'Customer portal' : 'At the clinic') : 'Not signed yet' }}</td></tr>
        <tr><th>Reviewed by</th><td>{{ $waiver->reviewer ? 'Dr. ' . $waiver->reviewer->full_name . ' · ' . $waiver->reviewed_at->format('F j, Y g:i A') : '—' }}</td></tr>
        @if ($waiver->review_notes)
          <tr><th>Review notes</th><td style="white-space: pre-line;">{{ $waiver->review_notes }}</td></tr>
        @endif
      </tbody>
    </table>

    @if ($staffCanSign)
      <form method="post" action="{{ route('staff.waivers.sign', $waiver) }}" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 18px;">
        @csrf
        <h3 style="margin-bottom: 8px;">✍️ Sign at the Clinic</h3>
        <p style="color: #666; margin-bottom: 12px;">Let the owner (or their authorized representative) read the form above, then type their full name.</p>
        <div class="form-group">
          <label for="signer_name">Full Name of the Person Signing</label>
          <input type="text" id="signer_name" name="signer_name" value="{{ old('signer_name', $waiver->customer?->full_name) }}" required maxlength="150">
          @error('signer_name') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
        </div>
        <div class="form-group">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
            <input type="checkbox" name="agree" value="1" style="width: auto;" required>
            The owner read and agreed to this form.
          </label>
          @error('agree') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
        </div>
        <button class="admin-add-btn" type="submit">Sign Form</button>
      </form>
    @endif

    @if ($vetCanReview)
      <form method="post" action="{{ route('admin.waivers.review', $waiver) }}" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 18px;">
        @csrf
        @method('PATCH')
        <h3 style="margin-bottom: 8px;">🩺 Review</h3>
        <div class="form-group">
          <label for="review_notes">Notes (optional)</label>
          <textarea id="review_notes" name="review_notes" rows="2" placeholder="e.g. Consent confirmed before the operation">{{ old('review_notes') }}</textarea>
        </div>
        <button class="admin-add-btn" type="submit">Mark as Reviewed</button>
      </form>
    @endif
  </div>

  <div class="{{ $p }}-tools">
    <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.waivers.index') }}'">← Back to Waivers</button>
    <button class="action-view" type="button" onclick="window.open('{{ route($area . '.waivers.print', $waiver) }}', '_blank')">🖨️ Print / Save as PDF</button>
    @if ($area === 'staff')
      @can('delete', $waiver)
        <form method="post" action="{{ route('staff.waivers.destroy', $waiver) }}" style="display: inline;" onsubmit="return confirm('Cancel this unsigned waiver?');">
          @csrf
          @method('DELETE')
          <button class="action-edit" type="submit">Cancel Waiver</button>
        </form>
      @endcan
    @endif
  </div>
</main>
@endsection
