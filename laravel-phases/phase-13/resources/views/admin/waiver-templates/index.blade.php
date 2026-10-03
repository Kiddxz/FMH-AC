@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Waiver Form Templates')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Waiver Form Templates</h1>
      <p>The text staff use when preparing a waiver. Changes do not affect waivers that were already prepared.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-tools">
    <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('admin.waiver-templates.create') }}'">+ Add Form</button>
    <button class="action-view" type="button" onclick="window.location.href='{{ route('admin.waivers.index') }}'">← Waivers</button>
  </div>
  <div class="admin-table-card">
    <table class="admin-table">
      <thead>
        <tr><th>Title</th><th>Type</th><th>Times Used</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        @foreach ($templates as $template)
          <tr>
            <td>{{ $template->title }}</td>
            <td>{{ ucwords(str_replace('_', ' ', $template->waiver_type)) }}</td>
            <td>{{ $template->waivers_count }}</td>
            <td>@include('partials.status-badge', ['status' => $template->is_active ? 'active' : 'inactive'])</td>
            <td><button class="action-edit" type="button" onclick="window.location.href='{{ route('admin.waiver-templates.edit', $template) }}'">Edit</button></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</main>
@endsection
