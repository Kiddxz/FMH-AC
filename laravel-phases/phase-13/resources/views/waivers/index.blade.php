{{-- Waiver list for Staff (/staff/waivers), Vet/Admin (/admin/waivers) and Super Admin (/superadmin/waivers) --}}
@php
  $p = $area === 'superadmin' ? 'superadmin' : 'admin';   // the super admin pages use their own CSS classes
  $labels = ['pending' => 'Waiting for signature', 'signed' => 'Signed', 'reviewed' => 'Reviewed'];
@endphp
@extends('layouts.' . $area)
@section('title', 'FMH Animal Clinic | Waivers & Consent')
@section('body_class', ['staff' => 'admin-layout', 'admin' => 'admin-dashboard-page', 'superadmin' => 'superadmin-page'][$area])
@section('footer', '© 2026 FMH Animal Clinic | ' . ['staff' => 'Staff', 'admin' => 'Admin', 'superadmin' => 'Super Admin'][$area] . ' Panel')
@section('content')
<main class="{{ $p }}-container">
  <div class="{{ $p }}-page-heading">
    <div>
      <h1>Digital Waiver &amp; Consent Forms</h1>
      <p>{{ $area === 'superadmin' ? 'Monitor waiver and consent records (view only).' : 'Forms prepared for pet owners, signed at the clinic or in the customer portal.' }}</p>
    </div>
  </div>

  <div class="{{ $p }}-tools" style="margin-bottom: 15px;">
    @foreach ($labels as $value => $label)
      <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.waivers.index', ['status' => $value]) }}'"
              @if ($status === $value) style="outline: 2px solid #e89427;" @endif>{{ $label }}: {{ $counts[$value] ?? 0 }}</button>
    @endforeach
  </div>

  <form class="{{ $p }}-tools" method="get" action="{{ route($area . '.waivers.index') }}">
    <input type="search" name="search" value="{{ $search }}" placeholder="Search reference, owner or pet...">
    <select name="status" onchange="this.form.submit()" aria-label="Status">
      <option value="">All Status</option>
      @foreach ($labels as $value => $label)
        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
      @endforeach
    </select>
    <select name="template" onchange="this.form.submit()" aria-label="Form">
      <option value="">All Forms</option>
      @foreach ($templates as $template)
        <option value="{{ $template->id }}" @selected($templateId === $template->id)>{{ $template->title }}</option>
      @endforeach
    </select>
    <button class="{{ $p }}-add-btn" type="submit">Search</button>
    @if ($area === 'staff')
      @can('waivers.prepare')
        <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.waivers.create') }}'">+ Prepare Waiver</button>
      @endcan
    @endif
    @if ($area === 'admin')
      @can('waivers.review')
        <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.waiver-templates.index') }}'">Form Templates</button>
      @endcan
    @endif
  </form>

  <div class="{{ $p }}-table-card">
    <table class="{{ $p }}-table">
      <thead>
        <tr>
          <th>Form ID</th>
          <th>Form</th>
          <th>Customer</th>
          <th>Pet</th>
          <th>Prepared</th>
          <th>Signed</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($waivers as $waiver)
          <tr>
            <td>{{ $waiver->reference }}</td>
            <td>{{ $waiver->template?->title }}</td>
            <td>{{ $waiver->customer?->full_name }}</td>
            <td>{{ $waiver->pet?->name }}</td>
            <td>{{ $waiver->created_at->format('M j, Y') }}</td>
            <td>{{ $waiver->signed_at ? $waiver->signed_at->format('M j, Y') . ' (' . $waiver->signed_via . ')' : '—' }}</td>
            <td>@include('partials.waiver-status', ['status' => $waiver->status])</td>
            <td>
              <button class="action-view" type="button" onclick="window.location.href='{{ route($area . '.waivers.show', $waiver) }}'">View</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align: center; color: #777;">No waivers found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
    @include($area === 'superadmin' ? 'partials.superadmin-pager' : 'partials.pager', ['items' => $waivers])
  </div>
</main>
@endsection
