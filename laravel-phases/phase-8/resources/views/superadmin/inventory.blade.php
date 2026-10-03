@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Inventory')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Inventory Monitoring</h1>
      <p>Monitor medicines, vaccines, and clinic supplies (view only).</p>
    </div>
  </div>
  <form class="superadmin-tools" method="get" action="{{ route('superadmin.inventory') }}">
    <select name="category" onchange="this.form.submit()">
      <option value="">All Categories</option>
      @foreach (\App\Models\InventoryItem::CATEGORIES as $option)
        <option value="{{ $option }}" @selected($category === $option)>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
  </form>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Item</th>
          <th>Category</th>
          <th>Quantity</th>
          <th>Nearest Expiration Date</th>
          <th>Minimum Stock</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($items as $item)
          @php
            $stock = (int) $item->stock_total;
            $state = $stock <= 0 ? 'Out of Stock' : ($stock <= $item->reorder_level ? 'Low Stock' : 'Available');
            $expiry = $item->next_expiry ? \Illuminate\Support\Carbon::parse($item->next_expiry) : null;
          @endphp
          <tr>
            <td>{{ $item->name }}</td>
            <td>{{ ucfirst($item->category) }}</td>
            <td>{{ $stock }} {{ $item->unit }}</td>
            <td>
              {{ $expiry ? $expiry->format('F j, Y') : 'N/A' }}
              @if ($expiry && $expiry->isBefore(now()->addDays(30)))
                <span class="status inactive" style="margin-left: 6px;">Expiring soon</span>
              @endif
            </td>
            <td>{{ $item->reorder_level }}</td>
            <td>
              <span class="status {{ $state === 'Available' ? 'active' : 'inactive' }}" @if ($state === 'Out of Stock') style="background: #fce8e8; color: #b33a3a;" @endif>{{ $state }}</span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; color: #94a3b8;">No inventory items.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</main>
@endsection
