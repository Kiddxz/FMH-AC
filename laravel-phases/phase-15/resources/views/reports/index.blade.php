{{-- Reports for Staff, Vet/Admin and Super Admin. Every report has the same parts: cards, summary tables and a full list. --}}
@php
  $p = $area === 'superadmin' ? 'superadmin' : 'admin';   // the super admin pages use their own CSS classes
  $route = $area === 'superadmin' ? 'superadmin.reports' : $area . '.reports.index';
  $statuses = \App\Services\ClinicReports::STATUSES[$type];
@endphp
@extends('layouts.' . $area)
@section('title', 'FMH Animal Clinic | Reports')
@section('body_class', $area === 'superadmin' ? 'superadmin-page' : 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | ' . ['staff' => 'Staff', 'admin' => 'Admin', 'superadmin' => 'Super Admin'][$area] . ' Panel')
@section('content')
<main class="{{ $p }}-container">
  <div class="{{ $p }}-page-heading">
    <div>
      <h1>Reports</h1>
      <p>Choose a report and a date range. Every number is counted from the clinic records.</p>
    </div>
    <div class="{{ $p }}-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>

  <form class="{{ $p }}-tools" method="get" action="{{ route($route) }}" style="flex-wrap: wrap;" id="report-form">
    <select name="report" aria-label="Report" onchange="this.form.status.value = ''; this.form.submit();">
      @foreach (\App\Services\ClinicReports::TYPES as $value => $label)
        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
      @endforeach
    </select>
    <input type="date" name="from" value="{{ $filters['from'] }}" aria-label="From date" title="From" required>
    <input type="date" name="to" value="{{ $filters['to'] }}" aria-label="To date" title="To" required>
    <select name="status" aria-label="Status" @disabled(! $statuses)>
      <option value="">{{ $type === 'records' ? 'All Record Types' : 'All Status' }}</option>
      @foreach ($statuses as $value => $label)
        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
      @endforeach
    </select>
    <button class="{{ $p }}-add-btn" type="submit">Generate Report</button>
    <button class="action-view" type="button" onclick="window.open('{{ route($route, $filters + ['format' => 'print']) }}', '_blank')">🖨️ Print / PDF</button>
    <button class="action-view" type="button" onclick="window.location.href='{{ route($route, $filters + ['format' => 'csv']) }}'">⬇ Export CSV</button>
  </form>

  <p style="color: #64748b; margin: 0 0 18px;">
    <strong>{{ $report['title'] }}</strong> · {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('M j, Y') }} – {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('M j, Y') }}
    @if ($filters['status']) · {{ $statuses[$filters['status']] }} only @endif
    @if ($type === 'inventory') · stock is as of today; the date range applies to stock movements @endif
  </p>

  <section class="{{ $p }}-stats">
    @foreach ($report['cards'] as [$icon, $label, $value])
      <div class="{{ $p }}-stat-card">
        <div class="{{ $p }}-stat-icon">{{ $icon }}</div>
        <div>
          <span>{{ $label }}</span>
          <strong>{{ $value }}</strong>
        </div>
      </div>
    @endforeach
  </section>

  @foreach ($report['tables'] as [$title, $headers, $rows])
    @include('reports.table', ['p' => $p, 'title' => $title, 'headers' => $headers, 'rows' => $rows])
  @endforeach

  @include('reports.table', ['p' => $p, 'title' => $report['detail'][0] . ' (' . count($report['detail'][2]) . ')', 'headers' => $report['detail'][1], 'rows' => $report['detail'][2]])
</main>
@endsection
