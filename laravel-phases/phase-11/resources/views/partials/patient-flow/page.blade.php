{{-- Patient flow page content, shared by Staff and Vet/Admin. Use: @include('partials.patient-flow.page') --}}
<style>
  .flow-board { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; align-items: start; }
  .flow-column { background: white; border-radius: 12px; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08); padding: 12px; min-height: 120px; }
  .flow-column-head { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-radius: 8px; font-weight: bold; margin-bottom: 12px; }
  .flow-card { border: 1px solid #eee; border-left: 5px solid #ccc; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; font-size: 14px; }
  .flow-card p { margin: 4px 0; color: #444; }
  .flow-card-top { display: flex; justify-content: space-between; gap: 8px; align-items: center; }
  .flow-type { font-size: 11px; background: #f3f3f3; color: #666; padding: 3px 8px; border-radius: 12px; white-space: nowrap; }
  .flow-time { font-size: 12px; color: #777 !important; }
  .flow-actions { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-top: 8px; }
  .flow-actions select, .flow-actions input[type=text] { flex: 1; min-width: 0; padding: 7px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; }
  .flow-cancel summary { cursor: pointer; color: #d9534f; font-size: 13px; margin-top: 8px; }
  .flow-empty { color: #999; text-align: center; font-size: 14px; }
  @media (max-width: 1000px) { .flow-board { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media (max-width: 600px) { .flow-board { grid-template-columns: 1fr; } }
</style>
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Patient Flow</h1>
      <p>Pets at the clinic: waiting, ongoing, completed or cancelled. For clinic use only; customers are not notified.</p>
    </div>
    <div class="admin-date">
      📅 {{ $date->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-tools" method="get" action="{{ route($area . '.flow.index') }}">
    <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" onchange="this.form.submit()" aria-label="Date" style="max-width: 220px;">
    <select name="purpose" onchange="this.form.submit()" aria-label="Purpose">
      <option value="">All Purposes</option>
      @foreach (\App\Models\Service::PURPOSES as $option)
        <option value="{{ $option }}" @selected($purpose === $option)>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
    @if (! $date->isToday())
      <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.flow.index') }}'">Back to Today</button>
    @endif
    @if ($area === 'staff')
      @can('patient_flow.manage')
        <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.walk-ins.create') }}'">+ New Walk-in</button>
      @endcan
    @endif
    <span id="flow-updated" style="color: #777; font-size: 13px;">Updates every 30 seconds</span>
  </form>
  @error('status')
    <p style="color: #c0392b;">{{ $message }}</p>
  @enderror
  @error('cancel_reason')
    <p style="color: #c0392b;">{{ $message }}</p>
  @enderror
  @error('visit')
    <p style="color: #c0392b;">{{ $message }}</p>
  @enderror
  <div id="flow-board">
    @include('partials.patient-flow.board')
  </div>
</main>
<script>
  // Reload only the board every 30 seconds, but not while someone is typing or choosing in it
  setInterval(function () {
    var board = document.getElementById('flow-board');
    if (board.contains(document.activeElement) && document.activeElement !== document.body) return;
    if (board.querySelector('details[open]')) return;
    var url = new URL(window.location.href);
    url.searchParams.set('partial', '1');
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) { return response.ok ? response.text() : Promise.reject(); })
      .then(function (html) {
        board.innerHTML = html;
        document.getElementById('flow-updated').textContent = 'Updated ' + new Date().toLocaleTimeString();
      })
      .catch(function () {});
  }, 30000);
</script>
