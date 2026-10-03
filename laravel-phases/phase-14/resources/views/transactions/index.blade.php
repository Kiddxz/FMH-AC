{{-- Bills and payments for Staff (/staff/transactions), Vet/Admin (/admin/transactions) and Super Admin (/superadmin/transactions) --}}
@php
  $p = $area === 'superadmin' ? 'superadmin' : 'admin';   // the super admin pages use their own CSS classes
  $indexRoute = $area === 'superadmin' ? 'superadmin.transactions' : $area . '.transactions.index';
  $heading = ['staff' => 'Transactions', 'admin' => 'Payments', 'superadmin' => 'Sales & Transaction Records'][$area];
  $statuses = ['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid', 'void' => 'Void'];
  $methods = ['cash' => 'Cash', 'gcash' => 'GCash', 'maya' => 'Maya', 'card' => 'Card'];
@endphp
@extends('layouts.' . $area)
@section('title', 'FMH Animal Clinic | ' . $heading)
@section('body_class', $area === 'superadmin' ? 'superadmin-page' : 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | ' . ['staff' => 'Staff', 'admin' => 'Admin', 'superadmin' => 'Super Admin'][$area] . ' Panel')
@section('content')
<main class="{{ $p }}-container">
  <div class="{{ $p }}-page-heading">
    <div>
      <h1>{{ $heading }}</h1>
      <p>{{ $area === 'superadmin' ? 'Monitor recorded clinic sales and payments (view only).' : 'Bills and in-clinic payments (cash, GCash, Maya, card). No online payment.' }}</p>
    </div>
    <div class="{{ $p }}-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>

  <section class="{{ $p }}-stats">
    <div class="{{ $p }}-stat-card">
      <div class="{{ $p }}-stat-icon">💰</div>
      <div>
        <span>Collected Today ({{ $stats['todayCount'] }})</span>
        <strong>₱{{ number_format($stats['today'], 2) }}</strong>
      </div>
    </div>
    <div class="{{ $p }}-stat-card">
      <div class="{{ $p }}-stat-icon">⏳</div>
      <div>
        <span>Unpaid Balances ({{ $stats['balanceCount'] }})</span>
        <strong>₱{{ number_format($stats['balance'], 2) }}</strong>
      </div>
    </div>
    <div class="{{ $p }}-stat-card">
      <div class="{{ $p }}-stat-icon">✅</div>
      <div>
        <span>Paid Bills</span>
        <strong>{{ $stats['paid'] }}</strong>
      </div>
    </div>
    <div class="{{ $p }}-stat-card">
      <div class="{{ $p }}-stat-icon">❌</div>
      <div>
        <span>Void Bills</span>
        <strong>{{ $stats['void'] }}</strong>
      </div>
    </div>
  </section>

  <form class="{{ $p }}-tools" method="get" action="{{ route($indexRoute) }}" style="flex-wrap: wrap;">
    <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search receipt no., customer or pet...">
    <select name="status" aria-label="Status">
      <option value="">All Status</option>
      @foreach ($statuses as $value => $label)
        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
      @endforeach
    </select>
    <select name="method" aria-label="Payment method">
      <option value="">All Payment Methods</option>
      @foreach ($methods as $value => $label)
        <option value="{{ $value }}" @selected(($filters['method'] ?? null) === $value)>{{ $label }}</option>
      @endforeach
    </select>
    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="From date" title="From">
    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="To date" title="To">
    <button class="{{ $p }}-add-btn" type="submit">Search</button>
    @if ($area === 'staff')
      @can('pos.manage')
        <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.pos.create') }}'">+ New Bill</button>
      @endcan
    @endif
  </form>

  <div class="{{ $p }}-table-card">
    <table class="{{ $p }}-table">
      <thead>
        <tr>
          <th>Receipt No.</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Pet</th>
          <th>Type</th>
          <th>Total</th>
          <th>Paid</th>
          <th>Balance</th>
          <th>Method</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($transactions as $transaction)
          <tr>
            <td>{{ $transaction->receipt_number }}</td>
            <td>{{ $transaction->created_at->format('M j, Y g:i A') }}</td>
            <td>{{ $transaction->customer?->full_name ?? 'Walk-in buyer' }}</td>
            <td>{{ $transaction->pet?->name ?? '—' }}</td>
            <td>{{ \App\Models\Transaction::TYPES[$transaction->transaction_type] }}</td>
            <td>₱{{ number_format($transaction->total, 2) }}</td>
            <td>₱{{ number_format($transaction->amount_paid, 2) }}</td>
            <td>₱{{ number_format($transaction->balance, 2) }}</td>
            <td>{{ $transaction->payments->pluck('method')->unique()->map(fn ($m) => $methods[$m])->join(', ') ?: '—' }}</td>
            <td>@include('partials.transaction-status', ['status' => $transaction->status])</td>
            <td>
              <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.transactions.show', $transaction) }}'">View</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="11" style="text-align: center; color: #777;">No transactions found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
    @include($area === 'superadmin' ? 'partials.superadmin-pager' : 'partials.pager', ['items' => $transactions])
  </div>
</main>
@endsection
