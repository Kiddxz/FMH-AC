{{-- Patient flow page content, shared by Staff and Vet/Admin. Use: @include('partials.patient-flow.page') --}}
<style>
  /* Board: 4 columns, each with a soft colored header and white patient cards */
  .flow-board { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; align-items: start; }
  .flow-column { display: grid; gap: 12px; padding: 14px; border: 1px solid #eee6db; border-radius: 16px; background: #fffdfa; min-height: 140px; }
  .flow-column-head { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-radius: 12px; background: var(--flow-bg); color: var(--flow-color); font-weight: 700; font-size: 15px; }
  .flow-column-title { display: inline-flex; align-items: center; gap: 8px; }
  .flow-count { min-width: 28px; padding: 2px 9px; border-radius: 999px; background: #fff; color: var(--flow-color); font-size: 13px; text-align: center; }
  .flow-card { padding: 14px; border: 1px solid #eee6db; border-top: 3px solid var(--flow-color); border-radius: 12px; background: #fff; box-shadow: 0 2px 8px rgba(38, 54, 74, 0.05); font-size: 13.5px; transition: box-shadow 0.2s; }
  .flow-card:hover { box-shadow: 0 6px 16px rgba(38, 54, 74, 0.09); }
  .flow-card-top { display: flex; align-items: center; gap: 10px; }
  .flow-queue { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; min-width: 34px; height: 34px; padding: 0 6px; border-radius: 10px; background: var(--flow-bg); color: var(--flow-color); font-weight: 800; font-size: 14px; }
  .flow-pet { flex: 1; min-width: 0; }
  .flow-pet strong { display: block; color: #26364a; font-size: 15px; line-height: 1.25; overflow-wrap: anywhere; }
  .flow-pet small { color: #8a8f98; font-size: 12px; }
  .flow-type { flex-shrink: 0; padding: 3px 9px; border-radius: 999px; background: #f4f1ec; color: #6b7280; font-size: 11px; font-weight: 600; }
  .flow-info { display: grid; gap: 5px; margin: 12px 0 0; padding: 10px 0 0; border-top: 1px dashed #eee6db; }
  .flow-info div { display: flex; justify-content: space-between; gap: 10px; }
  .flow-info span:last-child { color: #374151; text-align: right; overflow-wrap: anywhere; }
  .flow-label { color: #8a8f98; font-size: 12px; }
  .flow-times { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
  .flow-times span { padding: 2px 8px; border-radius: 6px; background: #f6f4f0; color: #6b7280; font-size: 11.5px; }
  .flow-note { margin: 10px 0 0; padding: 8px 10px; border-radius: 8px; background: #faf7f2; color: #555; font-size: 12.5px; overflow-wrap: anywhere; }
  .flow-note-red { background: #fff1f1; color: #b03a37; }
  .flow-actions { display: flex; gap: 8px; align-items: center; margin-top: 12px; }
  .flow-actions select, .flow-actions input[type=text] { flex: 1; min-width: 0; height: 36px; padding: 0 10px; border: 1px solid #ddd5ca; border-radius: 8px; background: #fff; font-size: 13px; }
  .flow-board .flow-btn { display: inline-flex; align-items: center; justify-content: center; height: 36px; padding: 0 14px; border: none; border-radius: 8px; background: var(--flow-color); color: #fff; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; white-space: nowrap; transition: filter 0.15s; }
  .flow-board .flow-btn:hover { filter: brightness(0.93); }
  .flow-board .flow-btn-full { flex: 1; }
  .flow-board .flow-btn-light { background: var(--flow-bg); color: var(--flow-color); }
  .flow-board .flow-btn-red { background: #d9534f; }
  .flow-cancel { margin-top: 10px; }
  .flow-cancel summary { display: inline-block; list-style: none; cursor: pointer; color: #b03a37; font-size: 12.5px; font-weight: 600; }
  .flow-cancel summary::-webkit-details-marker { display: none; }
  .flow-cancel summary:hover { text-decoration: underline; }
  .flow-empty { margin: 0; padding: 22px 10px; border: 1px dashed #e4dccf; border-radius: 12px; color: #9ca3af; font-size: 13.5px; text-align: center; }
  @media (max-width: 1100px) { .flow-board { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
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
