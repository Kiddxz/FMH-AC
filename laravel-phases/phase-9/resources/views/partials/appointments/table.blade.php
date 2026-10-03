{{-- Appointment list with filters for the clinic. Use: @include('partials.appointments.table', ['area' => 'staff']) --}}
<form class="admin-tools" method="get" action="{{ route($area . '.appointments.index') }}">
  <input type="search" name="search" value="{{ $search }}" placeholder="Search by pet owner, pet name, or reference...">
  <select name="status" onchange="this.form.submit()">
    <option value="">All Status</option>
    @foreach (\App\Models\Appointment::STATUSES as $option)
      <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
    @endforeach
  </select>
  <select name="service" onchange="this.form.submit()">
    <option value="">All Services</option>
    @foreach ($services as $option)
      <option value="{{ $option->id }}" @selected($serviceId === $option->id)>{{ $option->name }}</option>
    @endforeach
  </select>
  <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" style="flex: none;">
  <button class="admin-add-btn" type="submit">Search</button>
  @can('appointments.manage')
    <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route($area . '.appointments.create') }}'">+ New Appointment</button>
  @endcan
</form>
<div class="admin-table-card">
  <div class="admin-panel-header">
    <div>
      <h2>Appointment List</h2>
      <p>Upcoming appointments first, then past ones. {{ $appointments->total() }} found.</p>
    </div>
  </div>
  <table class="admin-table">
    <thead>
      <tr>
        <th>Reference</th>
        <th>Pet Owner</th>
        <th>Pet</th>
        <th>Service</th>
        <th>Date</th>
        <th>Time</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($appointments as $appointment)
        <tr>
          <td>{{ $appointment->reference }}</td>
          <td>{{ $appointment->customer?->full_name }}</td>
          <td>{{ $appointment->pet?->name }}</td>
          <td>{{ $appointment->service?->name }}</td>
          <td>{{ $appointment->appointment_date->format('F j, Y') }}</td>
          <td>{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td>
          <td>@include('partials.status-badge', ['status' => $appointment->status])</td>
          <td style="white-space: nowrap;">
            <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.appointments.show', $appointment) }}'">View</button>
            @if (in_array($appointment->status, ['pending', 'confirmed']))
              @can('appointments.manage')
                <button class="action-edit" type="button" onclick="window.location.href='{{ route($area . '.appointments.edit', $appointment) }}'">Edit</button>
              @endcan
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" style="text-align: center; color: #777;">No appointments found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
  @include('partials.pager', ['items' => $appointments])
</div>
