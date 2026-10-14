{{-- Notification bell in the menu, like Facebook: click it to see the list.
     The number is the notifications not read yet; the list itself is loaded from /notifications when the bell is clicked.
     Clicking a notification, its check button ("Mark as read") or "Mark all as read" makes the number go down.
     What each role sees comes from App\Services\Notifications (new bookings, bills, waivers, inventory, ...).
     Nothing is sent outside the system.
     Use: @include('partials.inventory-bell', ['area' => 'staff'])   (area: staff, admin or superadmin) --}}
@php
  $noteCount = \App\Services\Notifications::unreadCount(auth()->user(), $area);
  $inventoryCount = \App\Services\InventoryAlerts::summary()['count'];
@endphp
<style>
  .notif { position: relative; }
  .notif-button { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; padding: 0; border: none; border-radius: 50%; background: transparent; color: inherit; font-size: 18px; cursor: pointer; }
  .notif-button:hover, .notif.open .notif-button { background: #fff1df; color: #e89427; }
  .notif-count { position: absolute; top: -2px; right: -4px; min-width: 18px; padding: 1px 5px; border-radius: 10px; background: #d9534f; color: #fff; font-size: 11px; font-weight: bold; line-height: 16px; text-align: center; }
  .notif-panel { display: none; position: absolute; top: calc(100% + 10px); right: -10px; z-index: 1000; width: 380px; max-width: calc(100vw - 24px); border: 1px solid #eee6db; border-radius: 14px; background: #fff; box-shadow: 0 12px 32px rgba(38, 54, 74, 0.18); text-align: left; overflow: hidden; }
  .notif.open .notif-panel { display: block; }
  .notif-panel, .notif-panel * { white-space: normal; }
  .notif-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 14px 16px 10px; border-bottom: 1px solid #f1ebe2; }
  .notif-head strong { display: block; color: #26364a; font-size: 17px; }
  .notif-head small { color: #8a8f98; font-size: 12px; }
  .notif .notif-read-all { flex-shrink: 0; padding: 6px 10px; border: none; border-radius: 8px; background: transparent; color: #3b6fb6; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; }
  .notif-read-all:hover { background: #e8f0fb; }
  .notif-read-all[hidden] { display: none; }
  .notif-list { max-height: 380px; overflow-y: auto; }
  .notif-row { position: relative; border-bottom: 1px solid #f6f1ea; }
  .notif-row.is-unread { background: #fff; }
  .notif .notif-item { font-weight: 400; }
  .notif-item { display: flex; gap: 12px; align-items: flex-start; padding: 11px 48px 11px 16px; color: #26364a; text-decoration: none; }
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
  .notif-row.is-read .notif-title { font-weight: 500; color: #4b5563; }
  .notif-row.is-read .notif-time { color: #8a8f98; font-weight: 500; }
  .notif-dot { position: absolute; top: 16px; right: 18px; width: 10px; height: 10px; border-radius: 50%; background: #3b6fb6; pointer-events: none; }
  .notif .notif-mark { position: absolute; top: 50%; right: 8px; display: none; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; border: 1px solid #eadfce; border-radius: 50%; background: #fff; color: #287a43; cursor: pointer; transform: translateY(-50%); }
  .notif-row.is-unread:hover .notif-dot, .notif-row.is-unread:focus-within .notif-dot { display: none; }
  .notif-row.is-unread:hover .notif-mark, .notif-row.is-unread:focus-within .notif-mark { display: inline-flex; }
  .notif-mark:hover { border-color: #287a43; background: #e6f5e9; }
  .notif-empty, .notif-loading { padding: 26px 16px; color: #8a8f98; text-align: center; font-size: 14px; }
  .notif-more { padding: 10px 16px; color: #8a8f98; text-align: center; font-size: 13px; }
  @media (hover: none) {
    .notif-dot { display: none; }
    .notif-row.is-unread .notif-mark { display: inline-flex; }
  }
  @media (max-width: 760px) {
    .notif-panel { position: fixed; top: 70px; left: 12px; right: 12px; width: auto; }
  }
</style>
<div class="notif" id="notif" data-read-url="{{ route('notifications.read') }}" data-read-all-url="{{ route('notifications.read-all') }}" data-token="{{ csrf_token() }}">
  <button type="button" class="notif-button" id="notif-button" aria-haspopup="true" aria-expanded="false" aria-controls="notif-panel"
          title="Notifications" aria-label="Notifications: {{ $noteCount }} unread (Inventory alerts: {{ $inventoryCount }})">🔔@if ($noteCount > 0)<span class="notif-count">{{ $noteCount > 99 ? '99+' : $noteCount }}</span>@endif</button>
  <div class="notif-panel" id="notif-panel" role="dialog" aria-label="Notifications">
    <div class="notif-head">
      <span>
        <strong>Notifications</strong>
        <small id="notif-summary">{{ $noteCount }} unread</small>
      </span>
      <button type="button" class="notif-read-all" id="notif-read-all" @if ($noteCount === 0) hidden @endif>Mark all as read</button>
    </div>
    <div class="notif-list" id="notif-list" data-url="{{ route('notifications') }}">
      <div class="notif-loading">Loading…</div>
    </div>
  </div>
</div>
<script>
  // Open / close the list (click the bell, click outside, or press Esc).
  // Each time it opens, the newest notifications are loaded from the server.
  // Clicking a notification, its check button or "Mark all as read" saves it as read and the number goes down.
  (function () {
    var box = document.getElementById('notif');
    var button = document.getElementById('notif-button');
    var list = document.getElementById('notif-list');
    var summary = document.getElementById('notif-summary');
    var readAll = document.getElementById('notif-read-all');

    // the red number on the bell, the "unread" text and the "Mark all as read" button
    function setCount(count) {
      count = Math.max(0, count);
      var badge = button.querySelector('.notif-count');
      if (count > 0) {
        if (!badge) {
          badge = document.createElement('span');
          badge.className = 'notif-count';
          button.appendChild(badge);
        }
        badge.textContent = count > 99 ? '99+' : count;
      } else if (badge) {
        badge.remove();
      }
      summary.textContent = count + ' unread';
      readAll.hidden = count === 0;
      button.setAttribute('aria-label', 'Notifications: ' + count + ' unread');
    }
    function currentCount() {
      var badge = button.querySelector('.notif-count');
      return badge ? parseInt(badge.textContent, 10) || 0 : 0;
    }
    function send(url, data) {
      return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': box.dataset.token },
        body: JSON.stringify(data || {})
      }).then(function (response) { return response.ok ? response.json() : Promise.reject(); })
        .then(function (result) { setCount(result.unread); return result; });
    }
    function showRead(row) {
      row.classList.remove('is-unread');
      row.classList.add('is-read');
      row.querySelectorAll('.notif-dot, .notif-mark').forEach(function (el) { el.remove(); });
    }
    // one notification is read: the number goes down right away, then it is saved
    function markRead(row) {
      if (!row.classList.contains('is-unread')) return Promise.resolve();
      showRead(row);
      setCount(currentCount() - 1);
      return send(box.dataset.readUrl, { key: row.dataset.key }).catch(function () {});
    }

    function load() {
      fetch(list.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (response) { return response.ok ? response.text() : Promise.reject(); })
        .then(function (html) {
          list.innerHTML = html;
          var items = list.querySelector('.notif-items');
          if (items) setCount(parseInt(items.dataset.unread, 10) || 0);
        })
        .catch(function () { list.innerHTML = '<div class="notif-empty">Could not load the notifications. Please refresh the page.</div>'; });
    }
    function setOpen(open) {
      box.classList.toggle('open', open);
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) load();
    }

    list.addEventListener('click', function (event) {
      var row = event.target.closest('.notif-row');
      if (!row) return;

      // the check button: read, but stay on this page
      if (event.target.closest('.notif-mark')) {
        event.preventDefault();
        event.stopPropagation();   // the button is removed, so the "click outside" check must not close the list
        markRead(row);
        return;
      }

      // the notification itself: read, then open its page
      var link = event.target.closest('.notif-item');
      if (link && row.classList.contains('is-unread') && !event.ctrlKey && !event.metaKey && !event.shiftKey && event.button === 0) {
        event.preventDefault();
        markRead(row).then(function () { window.location.href = link.href; });
      } else if (link) {
        markRead(row);
      }
    });
    readAll.addEventListener('click', function () {
      list.querySelectorAll('.notif-row.is-unread').forEach(showRead);
      setCount(0);
      send(box.dataset.readAllUrl).catch(function () {});
    });

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
