{{-- "Today at the Clinic" box for the Staff and Vet/Admin dashboards (capstone FR-REQ026 / Scope):
     current patient status, money collected today and the newest pet registrations. Use: @include('partials.clinic-today', ['area' => 'staff']) --}}
@php
  $visits = \App\Models\PatientVisit::whereDate('visit_date', today())->get();
  $collected = auth()->user()->can('transactions.view')
    ? \App\Models\Payment::whereDate('paid_at', today())->whereHas('transaction', fn ($q) => $q->where('status', '!=', 'void'))->sum('amount')
    : null;
  $newPets = \App\Models\Pet::with('customer')->latest()->latest('id')->limit(5)->get();
  $statusBoxes = [
    'waiting' => ['⏳ Waiting', '#e89427', '#fff1df'],
    'ongoing' => ['🩺 Ongoing', '#2e8b57', '#e6f5e9'],
    'completed' => ['✅ Completed', '#4169e1', '#e8f0ff'],
    'cancelled' => ['✖ Cancelled', '#d9534f', '#ffe5e5'],
  ];
@endphp
<div class="admin-table-card">
  <div class="admin-panel-header">
    <div>
      <h2>Today at the Clinic</h2>
      <p>Current patient status, payments collected today and the newest registered pets.</p>
    </div>
    <a href="{{ route($area . '.flow.index') }}">Open Patient Flow →</a>
  </div>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 22px;">
    <div>
      <h3 style="font-size: 16px; margin-bottom: 10px;">Patient Status Today</h3>
      <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
        @foreach ($statusBoxes as $status => [$label, $color, $bg])
          <div style="background: {{ $bg }}; color: {{ $color }}; border-radius: 10px; padding: 12px;">
            <div style="font-size: 13px; font-weight: 600;">{{ $label }}</div>
            <strong style="font-size: 24px;">{{ $visits->where('status', $status)->count() }}</strong>
          </div>
        @endforeach
      </div>
      @if ($collected !== null)
        <p style="margin-top: 12px;">💰 Collected today: <strong>₱{{ number_format($collected, 2) }}</strong></p>
      @endif
    </div>
    <div>
      <h3 style="font-size: 16px; margin-bottom: 10px;">Recent Pet Registrations</h3>
      @forelse ($newPets as $pet)
        <p style="margin: 6px 0; display: flex; justify-content: space-between; gap: 10px;">
          <a href="{{ route($area . '.pets.show', $pet) }}" style="color: #333;">{{ $pet->icon }} {{ $pet->name }}</a>
          <span style="color: #64748b;">{{ $pet->customer?->full_name }} · {{ $pet->created_at->format('M j') }}</span>
        </p>
      @empty
        <p style="color: #999;">No pets yet.</p>
      @endforelse
    </div>
  </div>
</div>
