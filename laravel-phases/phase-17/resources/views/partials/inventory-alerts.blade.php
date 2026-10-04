{{-- Low-stock and expiry alerts (FR-REQ016). Use: @include('partials.inventory-alerts', ['area' => 'staff'])
     $alerts is optional; it comes from App\Services\InventoryAlerts::summary() --}}
@php
  $alerts = $alerts ?? \App\Services\InventoryAlerts::summary();
  $limit = $limit ?? 5;
@endphp
@if ($alerts['count'] > 0)
  <div class="admin-table-card" style="border-left: 5px solid #e89427;">
    <div class="admin-panel-header">
      <div>
        <h2>🔔 Inventory Alerts ({{ $alerts['count'] }})</h2>
        <p>Low stock, stock expiring within {{ \App\Services\InventoryAlerts::expiringDays() }} days, and expired stock to dispose.</p>
      </div>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;">
      <div>
        <h3 style="font-size: 16px; color: #e89427; margin-bottom: 8px;">⚠️ Low / Out of Stock ({{ $alerts['low']->count() }})</h3>
        @forelse ($alerts['low']->take($limit) as $item)
          <p style="margin: 4px 0;"><a href="{{ route($area . '.inventory.show', $item) }}" style="color: #333;">{{ $item->name }}</a>
            — {{ (int) $item->usable_stock }} {{ $item->unit }} left (min {{ $item->reorder_level }})</p>
        @empty
          <p style="color: #999;">None</p>
        @endforelse
      </div>
      <div>
        <h3 style="font-size: 16px; color: #e89427; margin-bottom: 8px;">⏰ Expiring Soon ({{ $alerts['expiring']->count() }})</h3>
        @forelse ($alerts['expiring']->take($limit) as $batch)
          <p style="margin: 4px 0;"><a href="{{ route($area . '.inventory.show', $batch->item) }}" style="color: #333;">{{ $batch->item->name }}</a>
            — {{ $batch->quantity }} {{ $batch->item->unit }}, {{ $batch->expiration_date->format('M j, Y') }}</p>
        @empty
          <p style="color: #999;">None</p>
        @endforelse
      </div>
      <div>
        <h3 style="font-size: 16px; color: #d9534f; margin-bottom: 8px;">✖ Expired ({{ $alerts['expired']->count() }})</h3>
        @forelse ($alerts['expired']->take($limit) as $batch)
          <p style="margin: 4px 0;"><a href="{{ route($area . '.inventory.show', $batch->item) }}" style="color: #333;">{{ $batch->item->name }}</a>
            — {{ $batch->quantity }} {{ $batch->item->unit }}, expired {{ $batch->expiration_date->format('M j, Y') }}</p>
        @empty
          <p style="color: #999;">None</p>
        @endforelse
      </div>
    </div>
  </div>
@endif
