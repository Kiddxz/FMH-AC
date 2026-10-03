{{-- Alert bell in the menu: number of inventory alerts. Use: @include('partials.inventory-bell', ['area' => 'staff']) --}}
@can('inventory.view')
  @php $alertCount = \App\Services\InventoryAlerts::summary()['count']; @endphp
  <a href="{{ route($area . '.inventory.index') }}" title="Inventory alerts" aria-label="Inventory alerts: {{ $alertCount }}" style="position: relative;">🔔@if ($alertCount > 0)<span style="position: absolute; top: -8px; right: -12px; background: #d9534f; color: white; border-radius: 10px; padding: 1px 6px; font-size: 11px; font-weight: bold;">{{ $alertCount }}</span>@endif</a>
@endcan
