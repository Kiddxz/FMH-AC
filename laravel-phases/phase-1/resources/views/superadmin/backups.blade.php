@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Backup & Recovery')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Database Backup & Recovery</h1>
      <p>Manage system database backup and recovery functions.</p>
    </div>
  </div>
  <div class="superadmin-backup-card">
    <div>
      <h2>Database Backup</h2>
      <p>Create a backup of the system database.</p>
    </div>
    <button type="button" class="superadmin-add-btn" id="backupBtn">Create Backup</button>
  </div>
  <div class="superadmin-backup-card">
    <div>
      <h2>Database Recovery</h2>
      <p>Restore the system using an available database backup.</p>
    </div>
    <button type="button" class="action-edit" id="recoveryBtn">Recovery</button>
  </div>
  <div class="superadmin-message" id="backupMessage"></div>
</main>
@endsection
