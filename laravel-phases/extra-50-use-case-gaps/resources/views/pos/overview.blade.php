{{-- "POS System" page, VIEW ONLY, for the Vet/Admin (/admin/pos) and the Super Admin (/superadmin/pos):
     what the POS sells, today's POS sales and today's bills. The Staff/Cashier records the bills in Staff -> POS. --}}
@php
  $p = $area === 'superadmin' ? 'superadmin' : 'admin';   // the super admin pages use their own CSS classes
  $transactionsRoute = $area === 'superadmin' ? 'superadmin.transactions' : 'admin.transactions.index';
@endphp
@extends('layouts.' . $area)
@section('title', 'FMH Animal Clinic | POS System')
@section('body_class', $area === 'superadmin' ? 'superadmin-page' : 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | ' . ($area === 'superadmin' ? 'Super Admin' : 'Admin') . ' Panel')
@section('content')
<style>
  .pos-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; margin-bottom: 25px; }
  .pos-grid .{{ $p }}-table-card { margin-bottom: 0; }
  .pos-note { margin: 0 0 20px; padding: 12px 16px; border: 1px solid #eee6db; border-radius: 12px; background: #fffdfa; color: #64748b; font-size: 14px; }
  .fmh-app .pos-grid td.pos-low { color: #b03a37; font-weight: 700; }
  .pos-grid .{{ $p }}-table { min-width: 0; }
  /* the super admin cards have no inner space of their own */
  .superadmin-table-card > .admin-panel-header { margin-bottom: 14px; padding: 20px 20px 0; }
  @media (max-width: 1000px) { .pos-grid { grid-template-columns: 1fr; } }
</style>
<main class="{{ $p }}-container">
  <div class="{{ $p }}-page-heading">
    <div>
      <h1>POS System</h1>
      <p>What the clinic POS sells and today's POS sales (view only).</p>
    </div>
    <div class="{{ $p }}-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>

  <p class="pos-note">The Staff/Cashier records bills and payments in the POS. This page only shows them.</p>

  <section class="{{ $p }}-stats">
    <div class="{{ $p }}-stat-card">
      <div class="{{ $p }}-stat-icon">🧾</div>
      <div><span>Bills Today</span><strong>{{ $stats['bills'] }}</strong></div>
    </div>
    <div class="{{ $p }}-stat-card">
      <div class="{{ $p }}-stat-icon">📊</div>
      <div><span>Billed Today</span><strong>₱{{ number_format($stats['billed'], 2) }}</strong></div>
    </div>
    <div class="{{ $p }}-stat-card">
      <div class="{{ $p }}-stat-icon">💰</div>
      <div><span>Collected Today</span><strong>₱{{ number_format($stats['collected'], 2) }}</strong></div>
    </div>
    <div class="{{ $p }}-stat-card">
      <div class="{{ $p }}-stat-icon">⏳</div>
      <div><span>Balance of Today's Bills</span><strong>₱{{ number_format($stats['balance'], 2) }}</strong></div>
    </div>
  </section>

  <div class="pos-grid">
    <div class="{{ $p }}-table-card">
      <div class="admin-panel-header">
        <div>
          <h2>Services</h2>
          <p>Services the cashier can put on a bill.</p>
        </div>
      </div>
      <table class="{{ $p }}-table">
        <thead><tr><th>Service</th><th>Duration</th><th>Price</th></tr></thead>
        <tbody>
          @forelse ($services as $service)
            <tr>
              <td>{{ $service->name }}</td>
              <td>{{ $service->duration_minutes }} min</td>
              <td>₱{{ number_format($service->price, 2) }}</td>
            </tr>
          @empty
            <tr><td colspan="3" style="text-align: center; color: #777;">No services yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="{{ $p }}-table-card">
      <div class="admin-panel-header">
        <div>
          <h2>Products</h2>
          <p>Medicines, vaccines and supplies for sale, with the stock that is not expired.</p>
        </div>
      </div>
      <table class="{{ $p }}-table">
        <thead><tr><th>Product</th><th>In Stock</th><th>Price</th></tr></thead>
        <tbody>
          @forelse ($products as $product)
            @php $stock = (int) $product->stock; @endphp
            <tr>
              <td>{{ $product->name }}</td>
              <td class="{{ $stock <= (int) $product->reorder_level ? 'pos-low' : '' }}">{{ $stock }} {{ $product->unit }}</td>
              <td>₱{{ number_format($product->selling_price, 2) }}</td>
            </tr>
          @empty
            <tr><td colspan="3" style="text-align: center; color: #777;">No products for sale yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if ($byMethod->isNotEmpty())
    <div class="{{ $p }}-table-card" style="margin-bottom: 25px;">
      <div class="admin-panel-header">
        <div><h2>Collected Today by Payment Method</h2></div>
      </div>
      <table class="{{ $p }}-table">
        <thead><tr><th>Method</th><th>Payments</th><th>Amount</th></tr></thead>
        <tbody>
          @foreach ($byMethod as $row)
            <tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td><td>₱{{ number_format($row['amount'], 2) }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  <div class="{{ $p }}-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Today's Bills</h2>
        <p>Every bill made in the POS today.</p>
      </div>
      <a href="{{ route($transactionsRoute) }}">All transactions</a>
    </div>
    <table class="{{ $p }}-table">
      <thead><tr><th>Receipt No.</th><th>Time</th><th>Customer</th><th>Pet</th><th>Total</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse ($todayBills as $bill)
          <tr>
            <td>{{ $bill->receipt_number }}</td>
            <td>{{ $bill->created_at->format('g:i A') }}</td>
            <td>{{ $bill->customer?->full_name ?? 'Walk-in buyer' }}</td>
            <td>{{ $bill->pet?->name ?? '—' }}</td>
            <td>₱{{ number_format($bill->total, 2) }}</td>
            <td>₱{{ number_format($bill->balance, 2) }}</td>
            <td>@include('partials.transaction-status', ['status' => $bill->status])</td>
            <td><button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.transactions.show', $bill) }}'">View</button></td>
          </tr>
        @empty
          <tr><td colspan="8" style="text-align: center; color: #777;">No bills yet today.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</main>
@endsection
