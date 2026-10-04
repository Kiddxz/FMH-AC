{{-- One table of a report. Needs: $p (CSS prefix), $title, $headers, $rows --}}
<div class="{{ $p }}-table-card" style="overflow-x: auto;">
  <h2 style="margin-bottom: 12px; font-size: 20px;">{{ $title }}</h2>
  <table class="{{ $p }}-table">
    <thead>
      <tr>
        @foreach ($headers as $header)
          <th>{{ $header }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @forelse ($rows as $row)
        <tr>
          @foreach ($row as $cell)
            <td>{{ $cell }}</td>
          @endforeach
        </tr>
      @empty
        <tr>
          <td colspan="{{ count($headers) }}" style="text-align: center; color: #777;">No records in this period.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
