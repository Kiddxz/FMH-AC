{{-- Notification bell in the menu, like Facebook: click it to see the list.
     The number is counted here; the list itself is loaded from /notifications when the bell is clicked.
     What each role sees comes from App\Services\Notifications (new bookings, bills, waivers, inventory, ...).
     Nothing is sent outside the system.
     Use: @include('partials.inventory-bell', ['area' => 'staff'])   (area: staff, admin or superadmin) --}}
@php
  $noteCount = \App\Services\Notifications::for(auth()->user(), $area)->count();
  $inventoryCount = \App\Services\InventoryAlerts::summary()['count'];
@endphp
<style>
  .notif { position: relative; }
  .notif-button { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; padding: 0; border: none; border-radius: 50%; background: transparent; color: inherit; font-size: 18px; cursor: pointer; }
  .notif-button:hover, .notif.open .notif-button { background: #fff1df; color: #e89427; }
  .notif-count { position: absolute; top: -2px; right: -4px; min-width: 18px; padding: 1px 5px; border-radius: 10px; background: #d9534f; color: #fff; font-size: 11px; font-weight: bold; line-height: 16px; text-align: center; }
  .notif-panel { display: none; position: absolute; top: calc(100% + 10px); right: -10px; z-index: 1000; width: 360px; max-width: calc(100vw - 24px); border: 1px solid #eee6db; border-radius: 14px; background: #fff; box-shadow: 0 12px 32px rgba(38, 54, 74, 0.18); text-align: left; overflow: hidden; }
  .notif.open .notif-panel { display: block; }
  .notif-panel, .notif-panel * { white-space: normal; }
  .notif-head { display: flex; justify-content: space-between; align-items: baseline; padding: 14px 16px 10px; border-bottom: 1px solid #f1ebe2; }
  .notif-head strong { color: #26364a; font-size: 17px; }
  .notif-head small { color: #8a8f98; font-size: 12px; }
  .notif-list { max-height: 380px; overflow-y: auto; }
  .notif .notif-item { font-weight: 400; }
  .notif-item { display: flex; gap: 12px; align-items: flex-start; padding: 11px 16px; border-bottom: 1px solid #f6f1ea; color: #26364a; text-decoration: none; }
  .notif-item:hover { background: #fffaf3; }
  .notif-item > span:last-child { flex: 1; min-width: 0; }
  .notif-icon { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 34px; height: 34px; border-radius: 50%; font-size: 16px; }
  .notif-icon.red { background: #fde8e8; color: #d9534f; }
  .notif-icon.orange { background: #fff1df; color: #e89427; }
  .notif-icon.blue { background: #e8f0fb; color: #3b6fb6; }
  .notif-icon.green { background: #e6f5e9; color: #287a43; }
  .notif .notif-time { display: block; margin-top: 3px; color: #3b6fb6; font-size: 12px; font-weight: 600; }
  .notif-icon .ui-icon { color: inherit !important; }
  .notif-title { display: block; font-size: 14px; font-weight: 600; line-height: 1.3; }
  .notif .notif-text { display: block; margin-top: 2px; color: #6b7280; font-size: 12.5px; font-weight: 400; line-height: 1.35; overflow-wrap: anywhere; }
  .notif-empty, .notif-loading { padding: 26px 16px; color: #8a8f98; text-align: center; font-size: 14px; }
  .notif-more { padding: 10px 16px; color: #8a8f98; text-align: center; font-size: 13px; }
  .notif .notif-foot { color: #e89427; }
  .notif-foot { display: block; padding: 11px 16px; color: #e89427; font-size: 14px; font-weight: 600; text-align: center; text-decoration: none; }
  .notif-foot:hover { background: #fffaf3; }
  @media (max-width: 760px) {
    .notif-panel { position: fixed; top: 70px; left: 12px; right: 12px; width: auto; }
  }
</style>
<div class="notif" id="notif">
  <button type="button" class="notif-button" id="notif-button" aria-haspopup="true" aria-expanded="false" aria-controls="notif-panel"
          title="Notifications" aria-label="Notifications: {{ $noteCount }} (Inventory alerts: {{ $inventoryCount }})">🔔@if ($noteCount > 0)<span class="notif-count">{{ $noteCount > 99 ? '99+' : $noteCount }}</span>@endif</button>
  <div class="notif-panel" id="notif-panel" role="dialog" aria-label="Notifications">
    <div class="notif-head">
      <strong>Notifications</strong>
      <small>{{ $noteCount }} {{ \Illuminate\Support\Str::plural('item', $noteCount) }} to check</small>
    </div>
    <div class="notif-list" id="notif-list" data-url="{{ route('notifications') }}">
      <div class="notif-loading">Loading…</div>
    </div>
  </div>
</div>
<script>
  // Open / close the list (click the bell, click outside, or press Esc).
  // Each time it opens, the newest notifications are loaded from the server.
  (function () {
    var box = document.getElementById('notif');
    var button = document.getElementById('notif-button');
    var list = document.getElementById('notif-list');

    function load() {
      fetch(list.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (response) { return response.ok ? response.text() : Promise.reject(); })
        .then(function (html) { list.innerHTML = html; })
        .catch(function () { list.innerHTML = '<div class="notif-empty">Could not load the notifications. Please refresh the page.</div>'; });
    }
    function setOpen(open) {
      box.classList.toggle('open', open);
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) load();
    }
    button.addEventListener('click', function (event) {
      event.stopPropagation();
      setOpen(!box.classList.contains('open'));
    });
    document.addEventListener('click', function (event) {
      if (!box.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') setOpen(false);
    });
  })();
</script>
