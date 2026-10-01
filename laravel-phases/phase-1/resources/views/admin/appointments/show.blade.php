@extends('layouts.admin')

@section('title', 'FMH Animal Clinic | Appointment Details')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')

@section('content')
<main class="admin-container">

  <div class="admin-page-heading">

    <div>

      <h1>
        Appointment Details
      </h1>

      <p>
        View and manage appointment information.
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <h2>
        Appointment Information
      </h2>

      <span
        class="status pending"
        id="appointmentStatus"
      >
        Pending
      </span>

    </div>

    <table class="admin-table">

      <tbody>

        <tr>

          <th>
            Appointment ID
          </th>

          <td>
            APP-001
          </td>

        </tr>

        <tr>

          <th>
            Appointment Date
          </th>

          <td>
            August 12, 2026
          </td>

        </tr>

        <tr>

          <th>
            Appointment Time
          </th>

          <td>
            10:00 AM
          </td>

        </tr>

        <tr>

          <th>
            Service
          </th>

          <td>
            Consultation
          </td>

        </tr>

        <tr>

          <th>
            Pet Name
          </th>

          <td>
            Max
          </td>

        </tr>

        <tr>

          <th>
            Species
          </th>

          <td>
            Dog
          </td>

        </tr>

        <tr>

          <th>
            Breed
          </th>

          <td>
            Golden Retriever
          </td>

        </tr>

        <tr>

          <th>
            Owner
          </th>

          <td>
            Mark Santos
          </td>

        </tr>

        <tr>

          <th>
            Email
          </th>

          <td>
            marksantos@email.com
          </td>

        </tr>

        <tr>

          <th>
            Mobile
          </th>

          <td>
            09171234567
          </td>

        </tr>

        <tr>

          <th>
            Reason for Visit
          </th>

          <td>
            General health check-up.
          </td>

        </tr>

        <tr>

          <th>
            Additional Notes
          </th>

          <td>
            Owner requested a general health assessment for the pet.
          </td>

        </tr>

      </tbody>

    </table>

  </div>

  <div class="admin-tools">

    <button
      class="admin-add-btn"
      type="button"
      id="confirmAppointmentBtn"
    >
      Confirm Appointment
    </button>

    <button
      class="action-edit"
      type="button"
      id="editAppointmentBtn"
      onclick="window.location.href='{{ route('admin.appointments.edit', 1) }}'"
    >
      Edit Appointment
    </button>

    <button
      class="action-delete"
      type="button"
      id="cancelAppointmentBtn"
    >
      Cancel Appointment
    </button>

  </div>

</main>
@endsection
