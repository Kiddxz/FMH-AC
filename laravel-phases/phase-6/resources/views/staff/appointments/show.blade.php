@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Appointment Details')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Appointment Details</h1>
      <p>View and manage appointment information.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Appointment Information</h2>
        <p>Appointment ID: APP-001</p>
      </div>
      <span class="status pending">Pending</span>
    </div>
    <table class="admin-table">
      <tbody>
        <tr>
          <th>Appointment ID</th>
          <td>APP-001</td>
        </tr>
        <tr>
          <th>Appointment Date</th>
          <td>August 12, 2026</td>
        </tr>
        <tr>
          <th>Appointment Time</th>
          <td>10:00 AM</td>
        </tr>
        <tr>
          <th>Service</th>
          <td>Consultation</td>
        </tr>
        <tr>
          <th>Pet Name</th>
          <td>Max</td>
        </tr>
        <tr>
          <th>Species</th>
          <td>Dog</td>
        </tr>
        <tr>
          <th>Breed</th>
          <td>Golden Retriever</td>
        </tr>
        <tr>
          <th>Sex</th>
          <td>Male</td>
        </tr>
        <tr>
          <th>Age</th>
          <td>3 years old</td>
        </tr>
        <tr>
          <th>Owner</th>
          <td>Mark Santos</td>
        </tr>
        <tr>
          <th>Email</th>
          <td>marksantos@email.com</td>
        </tr>
        <tr>
          <th>Mobile</th>
          <td>09171234567</td>
        </tr>
        <tr>
          <th>Reason for Visit</th>
          <td>General health check-up.</td>
        </tr>
        <tr>
          <th>Additional Notes</th>
          <td>Owner requested a general health assessment for the pet.</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="admin-tools">
    <button class="admin-add-btn" type="button" onclick="confirmAppointment()">✓ Confirm Appointment</button>
    <button class="action-delete" type="button" onclick="cancelAppointment()">✕ Cancel Appointment</button>
    <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.appointments.index') }}'">← Back to Appointments</button>
  </div>
</main>
@endsection
@push('scripts')
<script>
    function confirmAppointment() {
      const confirmed = confirm(
        "Are you sure you want to confirm this appointment?"
      );
      if (confirmed) {
        alert(
          "Appointment confirmed successfully."
        );
        window.location.href =
          "{{ route('staff.appointments.index') }}";
      }
    }
    function cancelAppointment() {
      const cancelled = confirm(
        "Are you sure you want to cancel this appointment?"
      );
      if (cancelled) {
        alert(
          "Appointment cancelled successfully."
        );
        window.location.href =
          "{{ route('staff.appointments.index') }}";
      }
    }
  </script>
@endpush
