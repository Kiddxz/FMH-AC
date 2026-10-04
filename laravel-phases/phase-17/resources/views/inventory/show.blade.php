{{-- One inventory item: details, batches (with expiry), stock in / usage forms and its usage log --}}
@extends('layouts.' . $area)
@php
  $usable = $batches->filter(fn ($b) => ! $b->isExpired())->sum('quantity');
  $expiredQty = $batches->filter(fn ($b) => $b->isExpired())->sum('quantity');
  $canManage = $area === 'staff' && auth()->user()->can('inventory.manage');
  $types = ['stock_in' => 'Stock In', 'usage' => 'Usage', 'sale' => 'Sale', 'adjustment' => 'Adjustment', 'expired' => 'Expired'];
@endphp
@section('title', 'FMH Animal Clinic | ' . $item->name)
@section('body_class', $area === 'admin' ? 'admin-dashboard-page' : 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | ' . ($area === 'admin' ? 'Admin' : 'Staff') . ' Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $item->name }}</h1>
      <p>{{ $item->sku }} · {{ ucfirst($item->category) }} @unless ($item->is_active) · <strong style="color: #d9534f;">Inactive</strong> @endunless</p>
    </div>
    <div class="admin-date">
      📦 {{ $usable }} {{ $item->unit }} available
    </div>
  </div>

  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Item Information</h2>
      @if ($canManage)
        <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.inventory.edit', $item) }}'">Edit Item</button>
      @endif
    </div>
    <table class="admin-table">
      <tbody>
        <tr><th>Available Stock</th><td><strong>{{ $usable }} {{ $item->unit }}</strong>@if ($expiredQty > 0) <span style="color: #d9534f;">(+ {{ $expiredQty }} expired, to dispose)</span>@endif</td></tr>
        <tr><th>Minimum Stock (low-stock alert)</th><td>{{ $item->reorder_level }} {{ $item->unit }}</td></tr>
        <tr><th>Selling Price</th><td>{{ $item->selling_price !== null ? '₱' . number_format($item->selling_price, 2) : 'Not for sale' }}</td></tr>
        <tr><th>Main Supplier</th><td>{{ $item->supplier?->name ?? '—' }}</td></tr>
      </tbody>
    </table>
  </div>

  @error('quantity')
    <p style="color: #c0392b; margin-bottom: 15px;">{{ $message }}</p>
  @enderror
  @error('batch')
    <p style="color: #c0392b; margin-bottom: 15px;">{{ $message }}</p>
  @enderror

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 0 25px;">
    @if ($canManage)
      <form class="admin-table-card" method="post" action="{{ route('staff.inventory.stock-in', $item) }}">
        @csrf
        <div class="admin-panel-header">
          <h2>➕ Stock In (delivery)</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0 12px;">
          <div class="form-group">
            <label for="in_quantity">Quantity ({{ $item->unit }})</label>
            <input type="number" id="in_quantity" name="quantity" min="1" value="{{ old('quantity') }}" required>
          </div>
          <div class="form-group">
            <label for="batch_number">Batch / Lot No.</label>
            <input type="text" id="batch_number" name="batch_number" value="{{ old('batch_number') }}" placeholder="Optional">
            @error('batch_number') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
          </div>
          <div class="form-group">
            <label for="expiration_date">Expiration Date</label>
            <input type="date" id="expiration_date" name="expiration_date" value="{{ old('expiration_date') }}" min="{{ today()->addDay()->format('Y-m-d') }}">
            @error('expiration_date') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
          </div>
          <div class="form-group">
            <label for="received_date">Date Received</label>
            <input type="date" id="received_date" name="received_date" value="{{ old('received_date', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required>
            @error('received_date') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
          </div>
          <div class="form-group">
            <label for="unit_cost">Unit Cost (₱)</label>
            <input type="number" id="unit_cost" name="unit_cost" min="0" step="0.01" value="{{ old('unit_cost') }}" placeholder="Optional">
            @error('unit_cost') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
          </div>
          <div class="form-group">
            <label for="supplier_id">Supplier</label>
            <select id="supplier_id" name="supplier_id">
              <option value="">—</option>
              @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $item->supplier_id) === $supplier->id)>{{ $supplier->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <button class="admin-add-btn" type="submit">Save Stock In</button>
      </form>
    @endif

    @can('inventory.record_usage')
      <form class="admin-table-card" method="post" action="{{ route($area . '.inventory.usage', $item) }}">
        @csrf
        <div class="admin-panel-header">
          <h2>➖ Record Usage</h2>
        </div>
        <p style="color: #666; margin-bottom: 12px;">Taken from the batch that expires first. Expired stock is never used.</p>
        <div class="form-group">
          <label for="use_quantity">Quantity Used ({{ $item->unit }})</label>
          <input type="number" id="use_quantity" name="quantity" min="1" max="{{ max(1, $usable) }}" required>
        </div>
        <div class="form-group">
          <label for="use_remarks">Used For</label>
          <input type="text" id="use_remarks" name="remarks" value="{{ old('remarks') }}" placeholder="e.g. Max - consultation" required>
          @error('remarks') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
        </div>
        <button class="admin-add-btn" type="submit" @disabled($usable <= 0)>Save Usage</button>
      </form>
    @endcan
  </div>

  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Batches</h2>
    </div>
    @error('counted') <p style="color: #c0392b;">{{ $message }}</p> @enderror
    @error('reason') <p style="color: #c0392b;">{{ $message }}</p> @enderror
    <table class="admin-table">
      <thead>
        <tr><th>Batch / Lot</th><th>Supplier</th><th>Received</th><th>Expires</th><th>Quantity</th>@if ($canManage)<th>Actions</th>@endif</tr>
      </thead>
      <tbody>
        @forelse ($batches as $batch)
          <tr @if ($batch->quantity === 0) style="color: #aaa;" @endif>
            <td>{{ $batch->batch_number ?: '#' . $batch->id }}</td>
            <td>{{ $batch->supplier?->name ?? '—' }}</td>
            <td>{{ $batch->received_date->format('M j, Y') }}</td>
            <td>
              {{ $batch->expiration_date?->format('M j, Y') ?? 'No expiry' }}
              @if ($batch->quantity > 0 && $batch->isExpired())
                @include('partials.status-badge', ['status' => 'cancelled', 'label' => 'Expired'])
              @elseif ($batch->quantity > 0 && $batch->isExpiringWithin(\App\Services\InventoryAlerts::expiringDays()))
                @include('partials.status-badge', ['status' => 'pending', 'label' => 'Expiring soon'])
              @endif
            </td>
            <td>{{ $batch->quantity }} {{ $item->unit }}</td>
            @if ($canManage)
              <td>
                @if ($batch->quantity > 0 && $batch->isExpired())
                  <form method="post" action="{{ route('staff.inventory.dispose', $batch) }}" style="display: inline;" onsubmit="return confirm('Dispose {{ $batch->quantity }} expired {{ $item->unit }}?');">
                    @csrf
                    @method('PATCH')
                    <button class="action-edit" type="submit">Dispose Expired</button>
                  </form>
                @endif
                <details style="display: inline-block;">
                  <summary class="action-view" style="cursor: pointer; display: inline-block;">Correct Count</summary>
                  <form method="post" action="{{ route('staff.inventory.count', $batch) }}" style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                    @csrf
                    @method('PATCH')
                    <input type="number" name="counted" min="0" value="{{ $batch->quantity }}" required style="width: 90px; padding: 6px;" aria-label="Counted quantity">
                    <input type="text" name="reason" placeholder="Reason (e.g. monthly count)" required style="padding: 6px;" aria-label="Reason">
                    <button class="admin-add-btn" type="submit">Save</button>
                  </form>
                </details>
              </td>
            @endif
          </tr>
        @empty
          <tr><td colspan="6" style="text-align: center; color: #777;">No stock received yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Usage Log</h2>
    </div>
    <table class="admin-table">
      <thead>
        <tr><th>Date</th><th>Type</th><th>Quantity</th><th>Batch</th><th>Remarks</th><th>By</th></tr>
      </thead>
      <tbody>
        @forelse ($movements as $movement)
          <tr>
            <td>{{ $movement->created_at->format('M j, Y g:i A') }}</td>
            <td>{{ $types[$movement->type] ?? $movement->type }}</td>
            <td style="font-weight: bold; color: {{ $movement->quantity >= 0 ? '#2e8b57' : '#d9534f' }};">{{ sprintf('%+d', $movement->quantity) }}</td>
            <td>{{ $movement->batch?->batch_number ?? '—' }}</td>
            <td>{{ $movement->remarks ?: '—' }}</td>
            <td>{{ $movement->user?->full_name ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align: center; color: #777;">No movements yet.</td></tr>
        @endforelse
      </tbody>
    </table>
    @include('partials.pager', ['items' => $movements])
  </div>

  <div class="admin-tools">
    <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.inventory.index') }}'">← Back to Inventory</button>
  </div>
</main>
@endsection
