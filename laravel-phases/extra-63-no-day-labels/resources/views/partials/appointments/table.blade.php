{{-- Appointment list with filters for the clinic. Use: @include('partials.appointments.table', ['area' => 'staff'])
     Tabs: Upcoming (nearest date and time first), Past (latest first), All (upcoming first, then past). --}}
@php
  $tabs = ['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'];
  $listNote = [
    'upcoming' => 'Today and later, the nearest date and time first.',
    'past' => 'Earlier appointments, the latest first.',
    'all' => 'Upcoming first (nearest date and time on top), then past ones.',
  ][$view];
@endphp
@once
<style>
  .appt-tabs { display: inline-flex; gap: 4px; padding: 4px; border: 1px solid #eee6db; border-radius: 12px; background: #faf6f0; }
  .appt-tabs a { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 9px; color: #64748b; font-size: 14px; font-weight: 700; text-decoration: none; }
  .appt-tabs a:hover { color: #c46f0c; }
  .appt-tabs a.active { background: #fff; color: #c46f0c; box-shadow: 0 1px 4px rgba(38, 54, 74, 0.12); }
  .appt-tabs .count { padding: 1px 8px; border-radius: 999px; background: #fff1df; color: #c46f0c; font-size: 12px; }
  @media (max-width: 700px) { .appt-tabs { width: 100%; } .appt-tabs a { flex: 1; justify-content: center; padding: 8px 6px; } }
</style>
@endonce
<form class="admin-tools" method="get" action="{{ route($area . '.appointments.index') }}">
  @if (request()->filled('view'))
    {{-- a tab was clicked: searching and filtering stay inside that tab --}}
    <input type="hidden" name="view" value="{{ $view }}">
  @endif
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
      <p>{{ $listNote }} {{ $appointments->total() }} found.</p>
    </div>
    <nav class="appt-tabs" aria-label="Which appointments">
      @foreach ($tabs as $tabKey => $tabLabel)
        <a href="{{ route($area . '.appointments.index', array_filter(['view' => $tabKey, 'search' => $search, 'status' => $status, 'service' => $serviceId])) }}"
           class="{{ $view === $tabKey ? 'active' : '' }}" @if ($view === $tabKey) aria-current="page" @endif>
          {{ $tabLabel }}@if ($tabKey === 'upcoming' && $upcomingCount > 0)<span class="count">{{ $upcomingCount }}</span>@endif
        </a>
      @endforeach
    </nav>
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
          <td>{{ $appointment->service_names }}</td>
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
