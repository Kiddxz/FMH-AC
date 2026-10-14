{{-- Inventory list for Staff (/staff/inventory) and Vet/Admin (/admin/inventory) --}}
@extends('layouts.' . $area)
@section('title', 'FMH Animal Clinic | Inventory')
@section('body_class', $area === 'admin' ? 'admin-dashboard-page' : 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | ' . ($area === 'admin' ? 'Admin' : 'Staff') . ' Panel')
@section('content')
{{-- The 4 summary boxes on top, side by side. (The Staff pages already had this look; the Vet/Admin page did not.) --}}
<style>
  .fmh-app .admin-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; width: 100%; margin-bottom: 30px; }
  .fmh-app .admin-stat-card { display: flex; align-items: center; gap: 16px; min-height: 105px; padding: 22px; background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.07); box-sizing: border-box; }
  .fmh-app .admin-stat-icon { display: flex; justify-content: center; align-items: center; flex-shrink: 0; width: 55px; height: 55px; border-radius: 13px; background: #fff0dc; color: #e89427; font-size: 25px; }
  .fmh-app .admin-stat-card span { display: block; margin-bottom: 6px; color: #64748b; font-size: 14px; font-weight: 500; }
  .fmh-app .admin-stat-card strong { display: block; color: #26364a; font-size: 28px; font-weight: 700; }
  @media (max-width: 1100px) { .fmh-app .admin-stats { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 560px) { .fmh-app .admin-stats { grid-template-columns: 1fr 1fr; gap: 12px; } }
</style>
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Inventory</h1>
      <p>Manage and monitor clinic supplies and available stock.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-stats">
    <div class="admin-stat-card">
      <div class="admin-stat-icon">📦</div>
      <div>
        <span>Total Items</span>
        <strong>{{ $stats['total'] }}</strong>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon">✅</div>
      <div>
        <span>Available Items</span>
        <strong>{{ $stats['available'] }}</strong>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon">⚠️</div>
      <div>
        <span>Low Stock</span>
        <strong>{{ $stats['low'] }}</strong>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon">❌</div>
      <div>
        <span>Out of Stock</span>
        <strong>{{ $stats['out'] }}</strong>
      </div>
    </div>
  </div>

  @include('partials.inventory-alerts', ['alerts' => $alerts, 'area' => $area])

  <form class="admin-tools" method="get" action="{{ route($area . '.inventory.index') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search inventory item or code...">
    <select name="category" onchange="this.form.submit()" aria-label="Category">
      <option value="">All Categories</option>
      @foreach (\App\Models\InventoryItem::CATEGORIES as $option)
        <option value="{{ $option }}" @selected($category === $option)>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
    <select name="status" onchange="this.form.submit()" aria-label="Stock status">
      <option value="">All Stock Status</option>
      <option value="available" @selected($status === 'available')>Available</option>
      <option value="low" @selected($status === 'low')>Low Stock</option>
      <option value="out" @selected($status === 'out')>Out of Stock</option>
      <option value="expiring" @selected($status === 'expiring')>Expiring / Expired</option>
      <option value="inactive" @selected($status === 'inactive')>Inactive Items</option>
    </select>
    <button class="action-view" type="submit">Search</button>
    @if ($area === 'staff')
      @can('inventory.manage')
        <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.inventory.create') }}'">+ Add Item</button>
      @endcan
      @can('suppliers.manage')
        <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.suppliers.index') }}'">Suppliers</button>
      @endcan
    @endif
    <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.inventory.log') }}'">Usage Log</button>
  </form>

  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Inventory Items</h2>
        <p>Quantity counts only stock that is not expired.</p>
      </div>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Item</th>
          <th>Category</th>
          <th>Quantity</th>
          <th>Unit</th>
          <th>Minimum Stock</th>
          <th>Next Expiry</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($items as $item)
          @php
            $qty = (int) $item->stock_total;
            [$label, $badge] = ! $item->is_active ? ['Inactive', 'archived']
              : ($qty <= 0 ? ['Out of Stock', 'cancelled'] : ($qty <= $item->reorder_level ? ['Low Stock', 'pending'] : ['Available', 'completed']));
            $expiry = $item->next_expiry ? \Illuminate\Support\Carbon::parse($item->next_expiry) : null;
          @endphp
          <tr>
            <td>{{ $item->name }}<div style="color: #888; font-size: 12px;">{{ $item->sku }}</div></td>
            <td>{{ ucfirst($item->category) }}</td>
            <td>{{ $qty }}</td>
            <td>{{ $item->unit }}</td>
            <td>{{ $item->reorder_level }}</td>
            <td>
              @if ($expiry)
                <span @if ($expiry->lt(today())) style="color: #d9534f; font-weight: bold;" @elseif ($expiry->lte(today()->addDays(\App\Services\InventoryAlerts::expiringDays()))) style="color: #e89427; font-weight: bold;" @endif>{{ $expiry->format('M j, Y') }}</span>
              @else
                —
              @endif
            </td>
            <td>@include('partials.status-badge', ['status' => $badge, 'label' => $label])</td>
            <td>
              <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.inventory.show', $item) }}'">View</button>
              @if ($area === 'staff')
                @can('inventory.manage')
                  <button class="action-edit" type="button" onclick="window.location.href='{{ route('staff.inventory.edit', $item) }}'">Edit</button>
                @endcan
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align: center; color: #777;">No items found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
    @include('partials.pager', ['items' => $items])
  </div>
</main>
@endsection
