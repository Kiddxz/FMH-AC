{{-- Low-stock and expiry alerts (FR-REQ016). Use: @include('partials.inventory-alerts', ['area' => 'staff'])
     $alerts is optional; it comes from App\Services\InventoryAlerts::summary()
     The three boxes are plain (thin border, light background) like the other dashboard boxes; only the titles have color. --}}
@php
  $alerts = $alerts ?? \App\Services\InventoryAlerts::summary();
  $limit = $limit ?? 5;
@endphp
@if ($alerts['count'] > 0)
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>🔔 Inventory Alerts ({{ $alerts['count'] }})</h2>
        <p>Low stock, stock expiring within {{ \App\Services\InventoryAlerts::expiringDays() }} days, and expired stock to dispose.</p>
      </div>
      @unless (request()->routeIs('*.inventory.index'))
        <a href="{{ route($area . '.inventory.index') }}">Open Inventory →</a>
      @endunless
    </div>
    <div class="alert-columns">
      {{-- 1. Low / out of stock: the bar shows how much is left compared with the minimum stock --}}
      <div class="alert-box alert-low" style="border: 1px solid #eee6db; border-radius: 12px; background: #fffdfa;">
        <h3>⚠️ Low / Out of Stock ({{ $alerts['low']->count() }})</h3>
        @forelse ($alerts['low']->take($limit) as $item)
          @php $left = (int) $item->usable_stock; @endphp
          <a href="{{ route($area . '.inventory.show', $item) }}" class="alert-row">
            <span class="alert-name">{{ $item->name }}</span>
            <span class="alert-detail">
              {{ $left }} {{ $item->unit }} left (min {{ $item->reorder_level }})
              @if ($left <= 0)<span class="alert-tag tag-red">Out of stock</span>@endif
            </span>
            <span class="stock-bar"><span style="width: {{ $item->reorder_level > 0 ? min(100, round($left / $item->reorder_level * 100)) : 0 }}%;"></span></span>
          </a>
        @empty
          <p class="alert-none">✅ None</p>
        @endforelse
      </div>

      {{-- 2. Expiring soon --}}
      <div class="alert-box alert-expiring" style="border: 1px solid #eee6db; border-radius: 12px; background: #fffdfa;">
        <h3>⏰ Expiring Soon ({{ $alerts['expiring']->count() }})</h3>
        @forelse ($alerts['expiring']->take($limit) as $batch)
          @php $days = (int) today()->diffInDays($batch->expiration_date); @endphp
          <a href="{{ route($area . '.inventory.show', $batch->item) }}" class="alert-row">
            <span class="alert-name">{{ $batch->item->name }}</span>
            <span class="alert-detail">
              {{ $batch->quantity }} {{ $batch->item->unit }}, {{ $batch->expiration_date->format('M j, Y') }}
              <span class="alert-tag tag-orange">{{ $days === 0 ? 'today' : 'in ' . $days . ' ' . \Illuminate\Support\Str::plural('day', $days) }}</span>
            </span>
          </a>
        @empty
          <p class="alert-none">✅ None</p>
        @endforelse
      </div>

      {{-- 3. Expired: must be disposed --}}
      <div class="alert-box alert-expired" style="border: 1px solid #eee6db; border-radius: 12px; background: #fffdfa;">
        <h3>✖ Expired ({{ $alerts['expired']->count() }})</h3>
        @forelse ($alerts['expired']->take($limit) as $batch)
          <a href="{{ route($area . '.inventory.show', $batch->item) }}" class="alert-row">
            <span class="alert-name">{{ $batch->item->name }}</span>
            <span class="alert-detail">
              {{ $batch->quantity }} {{ $batch->item->unit }}, expired {{ $batch->expiration_date->format('M j, Y') }}
              <span class="alert-tag tag-red">Dispose</span>
            </span>
          </a>
        @empty
          <p class="alert-none">✅ None</p>
        @endforelse
      </div>
    </div>
  </div>
@endif
