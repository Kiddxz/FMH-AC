@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Edit Appointment')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>✏️ Edit Appointment</h1>
      <p>Update the appointment information below.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="appointment-form-card">
    <form id="editAppointmentForm">
      <div class="appointment-form-row">
        <div class="appointment-form-group">
          <label for="editOwnerName">Pet Owner</label>
          <input type="text" id="editOwnerName" value="Mark Santos" required>
        </div>
        <div class="appointment-form-group">
          <label for="editPetName">Pet Name</label>
          <input type="text" id="editPetName" value="Max" required>
        </div>
      </div>
      <div class="appointment-form-row">
        <div class="appointment-form-group">
          <label for="editServiceName">Service</label>
          <select id="editServiceName" required>
            <option value="Consultation" selected>Consultation</option>
            <option value="Vaccination">Vaccination</option>
            <option value="Grooming">Grooming</option>
          </select>
        </div>
        <div class="appointment-form-group">
          <label for="editStatus">Status</label>
          <select id="editStatus" required>
            <option value="Pending" selected>Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
      </div>
      <div class="appointment-form-row">
        <div class="appointment-form-group">
          <label for="editAppointmentDate">Appointment Date</label>
          <input type="date" id="editAppointmentDate" value="2026-08-12" required>
        </div>
        <div class="appointment-form-group">
          <label for="editAppointmentTime">Appointment Time</label>
          <input type="time" id="editAppointmentTime" value="10:00" required>
        </div>
      </div>
      <div class="appointment-form-group">
        <label for="editReason">Reason for Visit</label>
        <textarea id="editReason" rows="4" required>General health check-up.</textarea>
      </div>
      <div class="appointment-form-group">
        <label for="editNotes">Additional Notes</label>
        <textarea id="editNotes" rows="4">Owner requested a general health assessment for the pet.</textarea>
      </div>
      <div class="appointment-form-actions">
        <button type="button" class="appointment-cancel-btn" id="cancelEditBtn">Cancel</button>
        <button type="submit" class="appointment-save-btn">✏️ Save Changes</button>
      </div>
    </form>
  </div>
</main>
@endsection
