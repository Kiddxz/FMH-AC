@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Pet Records')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Assistant Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Pet Record</h1>
      <p>View the pet's information and medical history.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Pet Information</h2>
        <p>Complete information about the registered pet.</p>
      </div>
    </div>
    <table class="admin-table">
      <tbody>
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
          <th>Owner Email</th>
          <td>marksantos@email.com</td>
        </tr>
        <tr>
          <th>Owner Mobile</th>
          <td>09171234567</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="admin-table-card">
    <div class="admin-panel-header">
      <div>
        <h2>Medical History</h2>
        <p>Previous appointments and veterinary services.</p>
      </div>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Service</th>
          <th>Veterinarian</th>
          <th>Notes</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>August 12, 2026</td>
          <td>Consultation</td>
          <td>Dr. Maria Santos</td>
          <td>General health check-up.</td>
          <td>
            <span class="status pending">Pending</span>
          </td>
        </tr>
        <tr>
          <td>July 05, 2026</td>
          <td>Vaccination</td>
          <td>Dr. Maria Santos</td>
          <td>Annual vaccination completed.</td>
          <td>
            <span class="status completed">Completed</span>
          </td>
        </tr>
        <tr>
          <td>May 20, 2026</td>
          <td>Consultation</td>
          <td>Dr. Maria Santos</td>
          <td>Routine health assessment.</td>
          <td>
            <span class="status completed">Completed</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="admin-tools">
    <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.pets.index') }}'">← Back to Pets</button>
    <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.appointments.index') }}'">📅 View Appointments</button>
  </div>
</main>
@endsection
