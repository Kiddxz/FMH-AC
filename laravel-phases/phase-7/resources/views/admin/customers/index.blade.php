@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Customers')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Customers</h1>
      <p>View registered pet owners. Staff update customer details; the Super Admin manages accounts.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-tools" method="get" action="{{ route('admin.customers.index') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search by name, mobile or email...">
    <select name="type" onchange="this.form.submit()">
      <option value="">All Customers</option>
      <option value="portal" @selected($type === 'portal')>With Portal Account</option>
      <option value="walk_in" @selected($type === 'walk_in')>Without Portal Account</option>
    </select>
    <button class="admin-add-btn" type="submit">Search</button>
  </form>
  <div class="admin-table-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Mobile</th>
          <th>Number of Pets</th>
          <th>Registration Date</th>
          <th>Account</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($customers as $customer)
          <tr>
            <td>{{ $customer->full_name }}</td>
            <td>{{ $customer->email ?: '—' }}</td>
            <td>{{ $customer->contact_number }}</td>
            <td>{{ $customer->pets_count }}</td>
            <td>{{ $customer->created_at?->format('F j, Y') }}</td>
            <td>
              @if ($customer->user_id)
                <span class="user-status active">Portal</span>
              @else
                <span class="user-status inactive">Walk-in</span>
              @endif
            </td>
            <td>
              <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.customers.show', $customer) }}'">View</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; color: #777;">No customers found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
    @include('partials.pager', ['items' => $customers])
  </div>
</main>
@endsection
