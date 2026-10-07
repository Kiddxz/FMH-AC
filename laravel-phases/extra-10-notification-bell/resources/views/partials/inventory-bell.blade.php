{{-- Alert bell in the menu, like the notifications of Facebook: click it to see the list.
     Inventory alerts only (low / out of stock, expiring soon, expired). Nothing is sent outside the system.
     Use: @include('partials.inventory-bell', ['area' => 'staff']) --}}
@php
  $alerts = \App\Services\InventoryAlerts::summary();
  $alertCount = $alerts['count'];

  // One list, the most urgent first: expired, out of stock, low stock, expiring soon
  $notes = collect();
  foreach ($alerts['expired'] as $batch) {
      $notes->push(['icon' => '✖', 'tone' => 'red', 'item' => $batch->item, 'title' => $batch->item->name . ' has expired stock',
          'text' => $batch->quantity . ' ' . $batch->item->unit . ' expired on ' . $batch->expiration_date->format('M j, Y') . '. Please dispose of it.']);
  }
  foreach ($alerts['low'] as $item) {
      $left = (int) $item->usable_stock;
      $notes->push(['icon' => '⚠️', 'tone' => $left <= 0 ? 'red' : 'orange', 'item' => $item,
          'title' => $item->name . ($left <= 0 ? ' is out of stock' : ' is running low'),
          'text' => $left . ' ' . $item->unit . ' left (minimum ' . $item->reorder_level . '). Time to restock.']);
  }
  foreach ($alerts['expiring'] as $batch) {
      $days = (int) today()->diffInDays($batch->expiration_date);
      $notes->push(['icon' => '⏰', 'tone' => 'orange', 'item' => $batch->item, 'title' => $batch->item->name . ' expires ' . ($days === 0 ? 'today' : 'in ' . $days . ' ' . \Illuminate\Support\Str::plural('day', $days)),
          'text' => $batch->quantity . ' ' . $batch->item->unit . ', expiry ' . $batch->expiration_date->format('M j, Y') . '. Use it first.']);
  }
  $limit = 8;
@endphp
<style>
  .notif { position: relative; }
  .notif-button { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; padding: 0; border: none; border-radius: 50%; background: transparent; color: inherit; font-size: 18px; cursor: pointer; }
  .notif-button:hover, .notif.open .notif-button { background: #fff1df; color: #e89427; }
  .notif-count { position: absolute; top: -2px; right: -4px; min-width: 18px; padding: 1px 5px; border-radius: 10px; background: #d9534f; color: #fff; font-size: 11px; font-weight: bold; line-height: 16px; text-align: center; }
  .notif-panel { display: none; position: absolute; top: calc(100% + 10px); right: -10px; z-index: 1000; width: 360px; max-width: calc(100vw - 24px); border: 1px solid #eee6db; border-radius: 14px; background: #fff; box-shadow: 0 12px 32px rgba(38, 54, 74, 0.18); text-align: left; overflow: hidden; }
  .notif.open .notif-panel { display: block; }
  .notif-head { display: flex; justify-content: space-between; align-items: baseline; padding: 14px 16px 10px; border-bottom: 1px solid #f1ebe2; }
  .notif-head strong { color: #26364a; font-size: 17px; }
  .notif-head small { color: #8a8f98; font-size: 12px; }
  .notif-list { max-height: 380px; overflow-y: auto; }
  .notif .notif-item { font-weight: 400; }
  .notif-item { display: flex; gap: 12px; align-items: flex-start; padding: 11px 16px; border-bottom: 1px solid #f6f1ea; color: #26364a; text-decoration: none; }
  .notif-item:hover { background: #fffaf3; }
  .notif-icon { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 34px; height: 34px; border-radius: 50%; font-size: 16px; }
  .notif-icon.red { background: #fde8e8; color: #d9534f; }
  .notif-icon.orange { background: #fff1df; color: #e89427; }
  .notif-icon .ui-icon { color: inherit !important; }
  .notif-title { display: block; font-size: 14px; font-weight: 600; line-height: 1.3; }
  .notif .notif-text { display: block; margin-top: 2px; color: #6b7280; font-size: 12.5px; font-weight: 400; line-height: 1.35; }
  .notif-empty { padding: 26px 16px; color: #8a8f98; text-align: center; font-size: 14px; }
  .notif .notif-foot { color: #e89427; }
  .notif-foot { display: block; padding: 11px 16px; color: #e89427; font-size: 14px; font-weight: 600; text-align: center; text-decoration: none; }
  .notif-foot:hover { background: #fffaf3; }
  @media (max-width: 760px) {
    .notif-panel { position: fixed; top: 70px; left: 12px; right: 12px; width: auto; }
  }
</style>
<div class="notif" id="notif">
  <button type="button" class="notif-button" id="notif-button" aria-haspopup="true" aria-expanded="false" aria-controls="notif-panel"
          title="Notifications" aria-label="Inventory alerts: {{ $alertCount }}">🔔@if ($alertCount > 0)<span class="notif-count">{{ $alertCount > 99 ? '99+' : $alertCount }}</span>@endif</button>
  <div class="notif-panel" id="notif-panel" role="dialog" aria-label="Notifications">
    <div class="notif-head">
      <strong>Notifications</strong>
      <small>{{ $alertCount }} inventory {{ \Illuminate\Support\Str::plural('alert', $alertCount) }}</small>
    </div>
    <div class="notif-list">
      @forelse ($notes->take($limit) as $note)
        <a href="{{ route($area . '.inventory.show', $note['item']) }}" class="notif-item">
          <span class="notif-icon {{ $note['tone'] }}">{{ $note['icon'] }}</span>
          <span>
            <span class="notif-title">{{ $note['title'] }}</span>
            <span class="notif-text">{{ $note['text'] }}</span>
          </span>
        </a>
      @empty
        <div class="notif-empty">✅ All good! No inventory alerts right now.</div>
      @endforelse
    </div>
    <a href="{{ route($area . '.inventory.index') }}" class="notif-foot">
      {{ $notes->count() > $limit ? 'See all ' . $notes->count() . ' alerts in Inventory' : 'Open Inventory' }}
    </a>
  </div>
</div>
<script>
  // Open / close the notification list (click the bell, click outside, or press Esc)
  (function () {
    var box = document.getElementById('notif');
    var button = document.getElementById('notif-button');
    function setOpen(open) {
      box.classList.toggle('open', open);
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
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
