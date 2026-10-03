@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Services')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Services</h1>
      <p>View and manage clinic services. These prices are used for booking and the POS.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-tools" method="get" action="{{ route('admin.services.index') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search service...">
    <select name="purpose" onchange="this.form.submit()">
      <option value="">All Services</option>
      @foreach (\App\Models\Service::PURPOSES as $option)
        <option value="{{ $option }}" @selected($purpose === $option)>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
    <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('admin.services.create') }}'">+ Add New Service</button>
  </form>
  <div class="admin-table-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Service Name</th>
          <th>Description</th>
          <th>Price</th>
          <th>Duration</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($services as $service)
          <tr>
            <td>
              {{ $service->name }}<br>
              <small style="color: #888;">{{ ucfirst($service->purpose) }}</small>
            </td>
            <td>{{ $service->description ?: '—' }}</td>
            <td>₱{{ number_format($service->price, 2) }}</td>
            <td>{{ $service->duration_minutes >= 60 && $service->duration_minutes % 60 === 0 ? ($service->duration_minutes / 60) . ' ' . \Illuminate\Support\Str::plural('hour', $service->duration_minutes / 60) : $service->duration_minutes . ' minutes' }}</td>
            <td>
              <span class="user-status {{ $service->is_active ? 'active' : 'inactive' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span>
            </td>
            <td style="white-space: nowrap;">
              <button class="action-edit" type="button" onclick="window.location.href='{{ route('admin.services.edit', $service) }}'">Edit</button>
              <form action="{{ route('admin.services.toggle', $service) }}" method="post" style="display: inline;">
                @csrf
                @method('PATCH')
                <button class="action-view" type="submit">{{ $service->is_active ? 'Deactivate' : 'Activate' }}</button>
              </form>
              <form action="{{ route('admin.services.destroy', $service) }}" method="post" style="display: inline;" onsubmit="return confirm('Delete {{ addslashes($service->name) }}? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button class="action-delete" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; color: #777;">No services found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</main>
@endsection
