{{-- The 4 columns of the patient flow board. Reloaded by itself every 30 seconds (see partials/patient-flow/page).
     Needs: $area, $columns, $toCheckIn, $vets --}}
@php
  $titles = [
    'waiting' => ['⏳ Waiting', '#e89427', '#fff1df'],
    'ongoing' => ['🩺 Ongoing', '#2e8b57', '#e6f5e9'],
    'completed' => ['✅ Completed', '#4169e1', '#e8f0ff'],
    'cancelled' => ['✖ Cancelled', '#d9534f', '#ffe5e5'],
  ];
  $canManage = $area === 'staff' && auth()->user()->can('patient_flow.manage');

  // Phase 14: the cashier bills a completed visit. A visit (or its appointment) is billed only once.
  $canBill = $area === 'staff' && auth()->user()->can('pos.manage');
  $allVisits = $columns->flatten(1);
  $bills = $canBill
    ? \App\Models\Transaction::where('status', '!=', 'void')
        ->where(fn ($q) => $q->whereIn('patient_visit_id', $allVisits->pluck('id'))->orWhereIn('appointment_id', $allVisits->pluck('appointment_id')->filter()))
        ->get()
    : collect();
@endphp

@if ($toCheckIn->isNotEmpty())
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <h2>Today's Appointments Not Yet Checked In</h2>
    </div>
    <table class="admin-table">
      <thead>
        <tr><th>Time</th><th>Pet</th><th>Owner</th><th>Service</th><th>Status</th>@if ($canManage)<th>Actions</th>@endif</tr>
      </thead>
      <tbody>
        @foreach ($toCheckIn as $appointment)
          <tr>
            <td>{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td>
            <td>{{ $appointment->pet?->name }}</td>
            <td>{{ $appointment->customer?->full_name }}</td>
            <td>{{ $appointment->service?->name }}</td>
            <td>@include('partials.status-badge', ['status' => $appointment->status])</td>
            @if ($canManage)
              <td>
                <form method="post" action="{{ route('staff.flow.check-in', $appointment) }}" style="display: inline;">
                  @csrf
                  <button class="action-view" type="submit">Check In</button>
                </form>
              </td>
            @endif
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

<div class="flow-board">
  @foreach ($columns as $status => $visits)
    @php [$title, $color, $bg] = $titles[$status]; @endphp
    <div class="flow-column">
      <div class="flow-column-head" style="background: {{ $bg }}; color: {{ $color }};">
        <span>{{ $title }}</span>
        <strong>{{ $visits->count() }}</strong>
      </div>
      @forelse ($visits as $visit)
        <div class="flow-card" style="border-left-color: {{ $color }};">
          <div class="flow-card-top">
            <strong>#{{ $visit->queue_number }} {{ $visit->pet?->icon }} {{ $visit->pet?->name }}</strong>
            <span class="flow-type">{{ $visit->visit_type === 'walk_in' ? 'Walk-in' : 'Appointment' }}</span>
          </div>
          <p>{{ $visit->customer?->full_name }} · {{ $visit->customer?->contact_number }}</p>
          <p>{{ $visit->service?->name }}@if ($visit->veterinarian) · Dr. {{ $visit->veterinarian->last_name }}@endif</p>
          <p class="flow-time">
            In: {{ $visit->checked_in_at?->format('g:i A') }}
            @if ($visit->started_at) · Start: {{ $visit->started_at->format('g:i A') }} @endif
            @if ($visit->completed_at) · Done: {{ $visit->completed_at->format('g:i A') }} @endif
          </p>
          @if ($visit->notes)
            <p class="flow-time">📝 {{ $visit->notes }}</p>
          @endif
          @if ($visit->status === 'cancelled' && $visit->cancel_reason)
            <p class="flow-time" style="color: #d9534f;">Reason: {{ $visit->cancel_reason }}</p>
          @endif

          @if ($canManage && $visit->status === 'waiting')
            <form method="post" action="{{ route('staff.flow.status', $visit) }}" class="flow-actions">
              @csrf
              @method('PATCH')
              <input type="hidden" name="status" value="ongoing">
              <select name="veterinarian_id" aria-label="Veterinarian">
                <option value="">Vet (optional)</option>
                @foreach ($vets as $vet)
                  <option value="{{ $vet->id }}" @selected($visit->veterinarian_id === $vet->id)>Dr. {{ $vet->last_name }}</option>
                @endforeach
              </select>
              <button class="action-view" type="submit">Start</button>
            </form>
          @elseif ($canManage && $visit->status === 'ongoing')
            <form method="post" action="{{ route('staff.flow.status', $visit) }}" class="flow-actions">
              @csrf
              @method('PATCH')
              <input type="hidden" name="status" value="completed">
              <button class="admin-add-btn" type="submit">Complete</button>
            </form>
          @endif

          @if ($canManage && in_array($visit->status, ['waiting', 'ongoing'], true))
            <details class="flow-cancel">
              <summary>Cancel</summary>
              <form method="post" action="{{ route('staff.flow.status', $visit) }}" class="flow-actions">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <input type="text" name="cancel_reason" placeholder="Reason (e.g. owner left)" required maxlength="500">
                <button class="action-edit" type="submit">Confirm Cancel</button>
              </form>
            </details>
          @endif

          @if ($canBill && $visit->status === 'completed')
            @php $bill = $bills->first(fn ($t) => $t->patient_visit_id === $visit->id || ($visit->appointment_id && $t->appointment_id === $visit->appointment_id)); @endphp
            <div class="flow-actions">
              @if ($bill)
                <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.transactions.show', $bill) }}'">🧾 {{ $bill->receipt_number }} ({{ ucfirst($bill->status) }})</button>
              @else
                <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.pos.create', ['visit' => $visit->id]) }}'">💳 Bill</button>
              @endif
            </div>
          @endif

          @if ($area === 'admin' && in_array($visit->status, ['ongoing', 'completed'], true))
            @can('records.write')
              <div class="flow-actions">
                <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.records.create', ['pet' => $visit->pet_id, 'visit' => $visit->id]) }}'">Write Record</button>
              </div>
            @endcan
          @endif
        </div>
      @empty
        <p class="flow-empty">No patients.</p>
      @endforelse
    </div>
  @endforeach
</div>
