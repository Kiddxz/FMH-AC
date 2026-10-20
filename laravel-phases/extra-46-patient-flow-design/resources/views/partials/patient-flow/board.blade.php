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
            <td>{{ $appointment->service_names }}</td>
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
    <div class="flow-column" style="--flow-color: {{ $color }}; --flow-bg: {{ $bg }};">
      <div class="flow-column-head">
        <span class="flow-column-title">{{ $title }}</span>
        <span class="flow-count">{{ $visits->count() }}</span>
      </div>
      @forelse ($visits as $visit)
        <div class="flow-card">
          <div class="flow-card-top">
            <span class="flow-queue">#{{ $visit->queue_number }}</span>
            <span class="flow-pet">
              <strong>{{ $visit->pet?->name }}</strong>
              <small>{{ ucfirst((string) $visit->pet?->species) }}@if ($visit->pet?->breed) · {{ $visit->pet->breed }}@endif</small>
            </span>
            <span class="flow-type">{{ $visit->visit_type === 'walk_in' ? 'Walk-in' : 'Appointment' }}</span>
          </div>

          <div class="flow-info">
            <div><span class="flow-label">Owner</span><span>{{ $visit->customer?->full_name }}</span></div>
            <div><span class="flow-label">Mobile</span><span>{{ $visit->customer?->contact_number ?: '—' }}</span></div>
            <div><span class="flow-label">Service</span><span>{{ $visit->service?->name ?: '—' }}</span></div>
            @if ($visit->veterinarian)
              <div><span class="flow-label">Vet</span><span>Dr. {{ $visit->veterinarian->last_name }}</span></div>
            @endif
          </div>

          <div class="flow-times">
            <span>In {{ $visit->checked_in_at?->format('g:i A') }}</span>
            @if ($visit->started_at)<span>Start {{ $visit->started_at->format('g:i A') }}</span>@endif
            @if ($visit->completed_at)<span>Done {{ $visit->completed_at->format('g:i A') }}</span>@endif
          </div>

          @if ($visit->notes)
            <p class="flow-note">{{ $visit->notes }}</p>
          @endif
          @if ($visit->status === 'cancelled' && $visit->cancel_reason)
            <p class="flow-note flow-note-red">Reason: {{ $visit->cancel_reason }}</p>
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
              <button class="flow-btn" type="submit">Start</button>
            </form>
          @elseif ($canManage && $visit->status === 'ongoing')
            <form method="post" action="{{ route('staff.flow.status', $visit) }}" class="flow-actions">
              @csrf
              @method('PATCH')
              <input type="hidden" name="status" value="completed">
              <button class="flow-btn flow-btn-full" type="submit">Complete</button>
            </form>
          @endif

          @if ($canManage && in_array($visit->status, ['waiting', 'ongoing'], true))
            <details class="flow-cancel">
              <summary>Cancel visit</summary>
              <form method="post" action="{{ route('staff.flow.status', $visit) }}" class="flow-actions">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <input type="text" name="cancel_reason" placeholder="Reason (e.g. owner left)" required maxlength="500">
                <button class="flow-btn flow-btn-red" type="submit">Confirm</button>
              </form>
            </details>
          @endif

          @if ($canBill && $visit->status === 'completed')
            @php $bill = $bills->first(fn ($t) => $t->patient_visit_id === $visit->id || ($visit->appointment_id && $t->appointment_id === $visit->appointment_id)); @endphp
            <div class="flow-actions">
              @if ($bill)
                <a class="flow-btn flow-btn-light flow-btn-full" href="{{ route('staff.transactions.show', $bill) }}">{{ $bill->receipt_number }} ({{ ucfirst($bill->status) }})</a>
              @else
                <a class="flow-btn flow-btn-full" href="{{ route('staff.pos.create', ['visit' => $visit->id]) }}">Bill</a>
              @endif
            </div>
          @endif

          @if ($area === 'admin' && in_array($visit->status, ['ongoing', 'completed'], true))
            @can('records.write')
              <div class="flow-actions">
                <a class="flow-btn flow-btn-light flow-btn-full" href="{{ route('admin.records.create', ['pet' => $visit->pet_id, 'visit' => $visit->id]) }}">Write Record</a>
              </div>
            @endcan
          @endif
        </div>
      @empty
        <p class="flow-empty">No patients</p>
      @endforelse
    </div>
  @endforeach
</div>
