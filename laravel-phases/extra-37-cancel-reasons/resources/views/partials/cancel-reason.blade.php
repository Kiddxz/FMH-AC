{{-- "Cancel" for a booking, like cancelling an order in an online shop:
     the page shows only a Cancel button; clicking it opens a pop-up form.
     In the pop-up the owner picks a ready reason from the dropdown, types their own reason, or both.
     It is sent as "cancel_reason" (e.g. "I have a schedule conflict - I have work that day"), so the controllers stay the same.
     Use: @include('partials.cancel-reason', ['action' => route(...), 'method' => 'PATCH' or 'POST', 'reference' => 'APP-000123'])
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
  $old = (string) old('cancel_reason');
  $oldPick = collect($reasons)->first(fn ($r) => $old === $r || str_starts_with($old, $r . ' - '));
  $oldText = $oldPick ? trim(substr($old, strlen($oldPick) + 3)) : $old;
@endphp
<style>
  .cancel-modal { width: min(520px, calc(100vw - 24px)); max-height: calc(100vh - 24px); margin: auto; padding: 0; border: none; border-radius: 18px; box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3); color: #26364a; overflow: auto; }
  .cancel-modal::backdrop { background: rgba(20, 26, 36, 0.55); }
  .cancel-modal form { padding: 22px 22px 18px; }
  .cancel-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 4px; }
  .cancel-head h2 { margin: 0 !important; font-size: 21px !important; color: #26364a; }
  .cancel-x { flex-shrink: 0; width: 34px; height: 34px; border: none; border-radius: 50%; background: #f3f4f6; color: #26364a; font-size: 18px; line-height: 1; cursor: pointer; }
  .cancel-sub { margin: 0 0 16px; color: #6b7280; font-size: 14px; }
  .cancel-label { display: block; margin: 0 0 8px; color: #26364a; font-size: 14px; font-weight: bold; }
  .cancel-modal select { width: 100%; margin-bottom: 16px; padding: 12px 14px; border: 1px solid #ddd; border-radius: 10px; background: #fff; color: #26364a; font: inherit; font-size: 15px; cursor: pointer; box-sizing: border-box; }
  .cancel-modal select:focus { outline: none; border-color: #e04444; }
  .cancel-modal textarea { width: 100%; min-height: 90px; padding: 10px 12px; border: 1px solid #ddd; border-radius: 10px; font: inherit; font-size: 15px; resize: vertical; box-sizing: border-box; }
  .cancel-modal textarea:focus { outline: none; border-color: #e04444; }
  .cancel-count { display: block; margin-top: 4px; color: #9ca3af; font-size: 12px; text-align: right; }
  .cancel-error { margin: 10px 0 0; color: #c0392b; font-size: 14px; }
  .cancel-error[hidden] { display: none; }
  .cancel-actions { display: flex; gap: 10px; margin-top: 16px; }
  .cancel-actions button { flex: 1; padding: 13px 16px; border: none; border-radius: 10px; font: inherit; font-size: 15px; font-weight: bold; cursor: pointer; }
  .cancel-keep { background: #f3f4f6; color: #26364a; }
  .cancel-confirm { background: #e04444; color: #fff; }
  .cancel-confirm:hover { background: #c93636; }
  [data-open-cancel] { background: #e04444 !important; color: #fff !important; }
  [data-open-cancel]:hover { background: #c93636 !important; }
  /* on a phone the pop-up comes up from the bottom, like in shopping apps */
  @media (max-width: 600px) {
    .cancel-modal { width: 100vw; max-width: 100vw; max-height: 90vh; margin: auto 0 0; border-radius: 18px 18px 0 0; }
  }
  @media print { .cancel-modal, [data-open-cancel] { display: none !important; } }
</style>
<dialog class="cancel-modal" id="cancel-modal" aria-labelledby="cancel-title">
  <form action="{{ $action }}" method="post" id="cancel-form">
    @csrf
    @if (strtoupper($method) !== 'POST')
      @method($method)
    @endif
    <input type="hidden" name="cancel_reason" id="cancel_reason" value="{{ $old }}">
    <div class="cancel-head">
      <h2 id="cancel-title">Cancel Appointment</h2>
      <button type="button" class="cancel-x" data-close-cancel aria-label="Close">✕</button>
    </div>
    <p class="cancel-sub">Booking {{ $reference }}. Tell the clinic why you are cancelling.</p>

    <label class="cancel-label" for="cancel_pick">Choose a reason</label>
    <select id="cancel_pick">
      <option value="">Select a reason</option>
      @foreach ($reasons as $reason)
        <option value="{{ $reason }}" @selected($oldPick === $reason)>{{ $reason }}</option>
      @endforeach
    </select>

    <label class="cancel-label" for="cancel_text">Or write your own reason <span style="color: #9ca3af; font-weight: normal;">(you can also add details)</span></label>
    <textarea id="cancel_text" maxlength="400" placeholder="e.g. I have work on that day">{{ $oldText }}</textarea>
    <span class="cancel-count"><span id="cancel-count">0</span>/400</span>

    @error('cancel_reason')
      <p class="cancel-error">{{ $message }}</p>
    @enderror
    <p class="cancel-error" id="cancel-pick-error" hidden>Please choose a reason or write your own.</p>

    <div class="cancel-actions">
      <button type="button" class="cancel-keep" data-close-cancel>Keep Appointment</button>
      <button type="submit" class="cancel-confirm">Confirm Cancel</button>
    </div>
  </form>
</dialog>
<script>
  // The pop-up: open with the Cancel button, close with ✕ / "Keep Appointment" / Esc / a click outside.
  // The chosen reason and the typed text are joined into "cancel_reason".
  (function () {
    var modal = document.getElementById('cancel-modal');
    var form = document.getElementById('cancel-form');
    var hidden = document.getElementById('cancel_reason');
    var text = document.getElementById('cancel_text');
    var count = document.getElementById('cancel-count');
    var error = document.getElementById('cancel-pick-error');
    var pick = document.getElementById('cancel_pick');

    function open() { modal.showModal(); }
    function close() { modal.close(); error.hidden = true; }
    function counter() { count.textContent = text.value.length; }

    document.querySelectorAll('[data-open-cancel]').forEach(function (b) { b.addEventListener('click', open); });
    modal.querySelectorAll('[data-close-cancel]').forEach(function (b) { b.addEventListener('click', close); });
    modal.addEventListener('click', function (event) { if (event.target === modal) close(); });   // click outside
    modal.addEventListener('change', function () { error.hidden = true; });
    text.addEventListener('input', function () { counter(); error.hidden = true; });
    counter();

    form.addEventListener('submit', function (event) {
      var choice = pick.value;
      var typed = text.value.trim();
      var reason = choice ? (typed ? choice + ' - ' + typed : choice) : typed;
      if (!reason) {
        event.preventDefault();
        error.hidden = false;
        return;
      }
      hidden.value = reason;
    });

    // The form came back with an error: show the pop-up again
    @if ($errors->has('cancel_reason'))
      open();
    @endif
  })();
</script>
