{{-- "Cancel" for a booking: at first only a button; clicking it opens the box with ready reasons (and "Other").
     The chosen reason is sent as "cancel_reason", so the controllers stay the same.
     Use: @include('partials.cancel-reason', ['action' => route(...), 'method' => 'PATCH' or 'POST', 'reference' => 'APP-000123', 'boxClass' => 'pet-form' (optional)])
     Put the button anywhere on the page: <button type="button" data-open-cancel>✕ Cancel Appointment</button> --}}
@php
  $reasons = [
      'I have a schedule conflict',
      'My pet is feeling better',
      'I will book a different date or time',
      'My pet is already being treated somewhere else',
      'I have a transportation problem',
      'I booked by mistake',
  ];
  $old = old('cancel_reason');
  $oldIsPreset = in_array($old, $reasons, true);
@endphp
<style>
  .cancel-box { margin-top: 25px; }
  .cancel-box[hidden] { display: none; }
  .cancel-box h2 { margin-bottom: 6px !important; }
  .cancel-box > p { margin: 0 0 18px; color: #6b7280; }
  .cancel-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; }
  .cancel-option { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid #e5ddd2; border-radius: 10px; background: #fff; cursor: pointer; font-size: 15px; line-height: 1.35; }
  .cancel-option:hover { border-color: #e04444; }
  .cancel-option input { flex-shrink: 0; width: 18px; height: 18px; margin: 0; accent-color: #e04444; }
  .cancel-option:has(input:checked) { border-color: #e04444; background: #fdeeee; }
  .cancel-other[hidden] { display: none; }
  .cancel-other textarea { width: 100%; min-height: 90px !important; }
  .cancel-error { margin: 0 0 12px; color: #c0392b; font-size: 14px; }
  .cancel-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px; margin-top: 8px; }
  .cancel-actions button { padding: 13px 22px; border: none; border-radius: 8px; font: inherit; font-size: 15px; font-weight: bold; cursor: pointer; }
  .cancel-keep { background: #eee; color: #444; }
  .cancel-confirm { background: #e04444; color: #fff; }
  .cancel-confirm:hover { background: #c93636; }
  [data-open-cancel] { background: #e04444 !important; color: #fff !important; }
  [data-open-cancel]:hover { background: #c93636 !important; }
  [data-open-cancel][hidden] { display: none !important; }
  @media (max-width: 640px) { .cancel-options { grid-template-columns: 1fr; } }
  @media print { .cancel-box, [data-open-cancel] { display: none !important; } }
</style>
<form class="{{ $boxClass ?? 'pet-form' }} cancel-box" id="cancel-box" action="{{ $action }}" method="post" @unless ($errors->has('cancel_reason')) hidden @endunless>
  @csrf
  @if (strtoupper($method) !== 'POST')
    @method($method)
  @endif
  <input type="hidden" name="cancel_reason" id="cancel_reason" value="{{ $old }}">
  <h2>Cancel This Appointment</h2>
  <p>Why are you cancelling? Choose a reason so the clinic knows.</p>
  @error('cancel_reason')
    <p class="cancel-error">{{ $message }}</p>
  @enderror
  <div class="cancel-options">
    @foreach ($reasons as $reason)
      <label class="cancel-option">
        <input type="radio" name="cancel_pick" value="{{ $reason }}" @checked($old === $reason)>
        <span>{{ $reason }}</span>
      </label>
    @endforeach
    <label class="cancel-option">
      <input type="radio" name="cancel_pick" value="other" @checked($old && ! $oldIsPreset)>
      <span>Other reason</span>
    </label>
  </div>
  <div class="form-group cancel-other" id="cancel-other" @unless ($old && ! $oldIsPreset) hidden @endunless>
    <label for="cancel_other_text">Tell the clinic your reason</label>
    <textarea id="cancel_other_text" rows="3" maxlength="500" placeholder="Type your reason here">{{ $oldIsPreset ? '' : $old }}</textarea>
  </div>
  <p class="cancel-error" id="cancel-pick-error" hidden></p>
  <div class="cancel-actions">
    <button type="button" class="cancel-keep" id="cancel-keep">Keep My Appointment</button>
    <button type="submit" class="cancel-confirm">✕ Confirm Cancel</button>
  </div>
</form>
<script>
  // Open the box with the "Cancel" button; the chosen reason (or the typed one) goes into "cancel_reason"
  (function () {
    var box = document.getElementById('cancel-box');
    var hidden = document.getElementById('cancel_reason');
    var other = document.getElementById('cancel-other');
    var otherText = document.getElementById('cancel_other_text');
    var error = document.getElementById('cancel-pick-error');
    var openers = document.querySelectorAll('[data-open-cancel]');

    function picked() { return box.querySelector('input[name=cancel_pick]:checked'); }
    function showOpeners(show) { openers.forEach(function (b) { b.hidden = !show; }); }
    if (!box.hidden) showOpeners(false);

    openers.forEach(function (button) {
      button.addEventListener('click', function () {
        box.hidden = false;
        showOpeners(false);
        box.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
    document.getElementById('cancel-keep').addEventListener('click', function () {
      box.hidden = true;
      showOpeners(true);
      error.hidden = true;
    });
    box.addEventListener('change', function (event) {
      if (event.target.name !== 'cancel_pick') return;
      other.hidden = event.target.value !== 'other';
      error.hidden = true;
      if (!other.hidden) otherText.focus();
    });
    box.addEventListener('submit', function (event) {
      var choice = picked();
      var reason = !choice ? '' : (choice.value === 'other' ? otherText.value.trim() : choice.value);
      if (!reason) {
        event.preventDefault();
        error.textContent = !choice ? 'Please choose a reason.' : 'Please type your reason.';
        error.hidden = false;
        return;
      }
      hidden.value = reason;
      if (!confirm('Cancel booking {{ $reference }}?')) event.preventDefault();
    });
  })();
</script>
