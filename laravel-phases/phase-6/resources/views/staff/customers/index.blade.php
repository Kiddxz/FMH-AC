@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Customers')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Customers</h1>
      <p>Find pet owners by name, mobile number or email.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-tools" method="get" action="{{ route('staff.customers.index') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search by name, mobile number or email...">
    <button class="admin-add-btn" type="submit">Search</button>
    @if ($search !== '')
      <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.customers.index') }}'">Clear</button>
    @endif
  </form>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Customer List</h2>
        <p>{{ $customers->total() }} {{ \Illuminate\Support\Str::plural('customer', $customers->total()) }} found.</p>
      </div>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Mobile Number</th>
          <th>Email</th>
          <th>Pets</th>
          <th>Type</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($customers as $customer)
          <tr>
            <td>{{ $customer->full_name }}</td>
            <td>{{ $customer->contact_number }}</td>
            <td>{{ $customer->email ?: '—' }}</td>
            <td>{{ $customer->pets_count }}</td>
            <td>
              @if ($customer->user_id)
                <span class="status confirmed">Portal Account</span>
              @elseif ($customer->is_walk_in)
                <span class="status pending">Walk-in</span>
              @else
                <span class="status pending">No Portal Account</span>
              @endif
            </td>
            <td>
              <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.customers.show', $customer) }}'">View</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; color: #777;">No customers found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
    @include('partials.pager', ['items' => $customers])
  </div>
</main>
@endsection
