@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Backup & Recovery')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Database Backup &amp; Recovery</h1>
      <p>Save a copy of all clinic records ({{ $tables }} tables), and bring the records back from a copy if something goes wrong.</p>
    </div>
  </div>

  <div class="superadmin-backup-card">
    <div>
      <h2>Database Backup</h2>
      <p>Create a backup of the system database now. Download it and keep a copy outside this computer (e.g. a USB drive).</p>
    </div>
    <form method="post" action="{{ route('superadmin.backups.store') }}">
      @csrf
      <button type="submit" class="superadmin-add-btn">Create Backup</button>
    </form>
  </div>

  <div class="superadmin-backup-card">
    <div>
      <h2>Database Recovery</h2>
      <p>Restore with the <strong>Restore</strong> button of a backup below. A backup file you downloaded before can be uploaded here first.</p>
    </div>
    <form method="post" action="{{ route('superadmin.backups.upload') }}" enctype="multipart/form-data" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
      @csrf
      <input type="file" name="backup_file" accept=".json,application/json" required aria-label="Backup file">
      <button type="submit" class="action-edit">Upload Backup File</button>
    </form>
  </div>

  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Backup File</th>
          <th>Size</th>
          <th>Created</th>
          <th>Created By</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($backups as $backup)
          <tr>
            <td>{{ $backup->filename }}</td>
            <td style="white-space: nowrap;">{{ $backup->size_bytes >= 1048576 ? number_format($backup->size_bytes / 1048576, 1) . ' MB' : number_format(max($backup->size_bytes / 1024, 0.1), 1) . ' KB' }}</td>
            <td style="white-space: nowrap;">{{ $backup->created_at->format('M j, Y g:i A') }}</td>
            <td>{{ $backup->creator?->full_name ?? '—' }}</td>
            <td>
              <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                <button type="button" class="action-view" onclick="window.location.href='{{ route('superadmin.backups.download', $backup) }}'">⬇ Download</button>
                <form method="post" action="{{ route('superadmin.backups.destroy', $backup) }}" onsubmit="return confirm('Delete {{ $backup->filename }}? The file will be removed from the server.');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="action-delete">Delete</button>
                </form>
              </div>
              <details style="margin-top: 8px;">
                <summary style="cursor: pointer; color: #d9534f; font-weight: 600;">♻ Restore</summary>
                <form method="post" action="{{ route('superadmin.backups.restore', $backup) }}" style="margin-top: 10px; display: grid; gap: 8px; max-width: 320px;"
                      onsubmit="return confirm('Replace ALL current clinic records with this backup?');">
                  @csrf
                  <p style="margin: 0; color: #64748b; font-size: 13px;">All current clinic records will be replaced by this backup. A safety backup of the current records is made first. The activity log is kept.</p>
                  <input type="text" name="confirm_text" placeholder="Type RESTORE" required autocomplete="off" aria-label="Type RESTORE">
                  <input type="password" name="password" placeholder="Your password" required autocomplete="current-password" aria-label="Your password">
                  <button type="submit" class="action-delete">Restore This Backup</button>
                </form>
              </details>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align: center; color: #94a3b8;">No backups yet. Click "Create Backup" to make the first one.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.superadmin-pager', ['items' => $backups])
</main>
@endsection
