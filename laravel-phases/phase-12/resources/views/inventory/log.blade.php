{{-- Usage log: every stock change of every item (FR-REQ015) --}}
@extends('layouts.' . $area)
@php
  $types = ['stock_in' => 'Stock In', 'usage' => 'Usage', 'sale' => 'Sale', 'adjustment' => 'Adjustment', 'expired' => 'Expired'];
@endphp
@section('title', 'FMH Animal Clinic | Inventory Usage Log')
@section('body_class', $area === 'admin' ? 'admin-dashboard-page' : 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | ' . ($area === 'admin' ? 'Admin' : 'Staff') . ' Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Inventory Usage Log</h1>
      <p>Every stock change: deliveries, usage, sales, corrections and expired stock. Rows are never edited.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-tools" method="get" action="{{ route($area . '.inventory.log') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Item name or code...">
    <select name="type" aria-label="Type">
      <option value="">All Types</option>
      @foreach ($types as $value => $label)
        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
      @endforeach
    </select>
    <input type="date" name="from" value="{{ $from }}" aria-label="From" style="max-width: 180px;">
    <input type="date" name="to" value="{{ $to }}" aria-label="To" style="max-width: 180px;">
    <button class="admin-add-btn" type="submit">Filter</button>
    <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.inventory.index') }}'">← Inventory</button>
  </form>
  <div class="admin-table-card">
    <table class="admin-table">
      <thead>
        <tr><th>Date</th><th>Item</th><th>Type</th><th>Quantity</th><th>Batch</th><th>Remarks</th><th>By</th></tr>
      </thead>
      <tbody>
        @forelse ($movements as $movement)
          <tr>
            <td>{{ $movement->created_at->format('M j, Y g:i A') }}</td>
            <td><a href="{{ route($area . '.inventory.show', $movement->item) }}" style="color: #333;">{{ $movement->item?->name }}</a></td>
            <td>{{ $types[$movement->type] ?? $movement->type }}</td>
            <td style="font-weight: bold; color: {{ $movement->quantity >= 0 ? '#2e8b57' : '#d9534f' }};">{{ sprintf('%+d', $movement->quantity) }} {{ $movement->item?->unit }}</td>
            <td>{{ $movement->batch?->batch_number ?? '—' }}</td>
            <td>{{ $movement->remarks ?: '—' }}</td>
            <td>{{ $movement->user?->full_name ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align: center; color: #777;">No movements found.</td></tr>
        @endforelse
      </tbody>
    </table>
    @include('partials.pager', ['items' => $movements])
  </div>
</main>
@endsection
