{{-- Printable report. The browser's print window can also "Save as PDF". --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['title'] }} | FMH Animal Clinic</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; max-width: 1000px; margin: 25px auto; padding: 0 20px; font-size: 13px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #e89427; padding-bottom: 10px; margin-bottom: 15px; }
        h1 { margin: 0; font-size: 22px; }
        h2 { font-size: 16px; margin: 22px 0 8px; }
        .muted { color: #666; }
        .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .card { border: 1px solid #ddd; border-radius: 6px; padding: 10px; }
        .card strong { display: block; font-size: 18px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px 7px; text-align: left; }
        th { background: #fdf3e6; }
        .actions { margin-bottom: 15px; }
        .actions button { padding: 10px 18px; background: #e89427; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        @media print { .actions { display: none; } body { margin: 0 auto; } th { background: #eee; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">🖨️ Print / Save as PDF</button></div>
    <div class="head">
        <div>
            <h1>🐾 {{ \App\Models\Setting::get('clinic_name') }}</h1>
            <div class="muted">{{ \App\Models\Setting::get('clinic_address') }} · {{ \App\Models\Setting::get('clinic_contact') }}</div>
            <div class="muted">{{ $report['title'] }}</div>
        </div>
        <div style="text-align: right;">
            <strong>{{ \Illuminate\Support\Carbon::parse($filters['from'])->format('M j, Y') }} – {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('M j, Y') }}</strong>
            <div class="muted">Status: {{ $filters['status'] ? \App\Services\ClinicReports::STATUSES[$type][$filters['status']] : 'All' }}</div>
            <div class="muted">Printed {{ now()->format('F j, Y g:i A') }} by {{ auth()->user()->full_name }}</div>
        </div>
    </div>

    <div class="cards">
        @foreach ($report['cards'] as [$icon, $label, $value])
            <div class="card">{{ $icon }} {{ $label }}<strong>{{ $value }}</strong></div>
        @endforeach
    </div>

    @foreach (array_merge($report['tables'], [[$report['detail'][0] . ' (' . count($report['detail'][2]) . ')', $report['detail'][1], $report['detail'][2]]]) as [$title, $headers, $rows])
        <h2>{{ $title }}</h2>
        <table>
            <thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($headers) }}" class="muted">No records in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
