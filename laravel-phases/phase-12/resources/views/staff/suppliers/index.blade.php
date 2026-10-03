@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Suppliers')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Suppliers</h1>
      <p>Where the clinic gets its medicines, vaccines, supplies and products.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-tools" method="get" action="{{ route('staff.suppliers.index') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search supplier or contact person...">
    <button class="action-view" type="submit">Search</button>
    <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.suppliers.create') }}'">+ Add Supplier</button>
    <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.inventory.index') }}'">← Inventory</button>
  </form>
  <div class="admin-table-card">
    <table class="admin-table">
      <thead>
        <tr><th>Supplier</th><th>Contact Person</th><th>Contact No.</th><th>Email</th><th>Items</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        @forelse ($suppliers as $supplier)
          <tr>
            <td>{{ $supplier->name }}<div style="color: #888; font-size: 12px;">{{ $supplier->address }}</div></td>
            <td>{{ $supplier->contact_person ?: '—' }}</td>
            <td>{{ $supplier->contact_number ?: '—' }}</td>
            <td>{{ $supplier->email ?: '—' }}</td>
            <td>{{ $supplier->items_count }}</td>
            <td>@include('partials.status-badge', ['status' => $supplier->is_active ? 'active' : 'inactive'])</td>
            <td>
              <button class="action-edit" type="button" onclick="window.location.href='{{ route('staff.suppliers.edit', $supplier) }}'">Edit</button>
              <form method="post" action="{{ route('staff.suppliers.toggle', $supplier) }}" style="display: inline;">
                @csrf
                @method('PATCH')
                <button class="action-view" type="submit">{{ $supplier->is_active ? 'Deactivate' : 'Activate' }}</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align: center; color: #777;">No suppliers found.</td></tr>
        @endforelse
      </tbody>
    </table>
    @include('partials.pager', ['items' => $suppliers])
  </div>
</main>
@endsection
