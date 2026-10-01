@extends('layouts.staff')

@section('title', 'FMH Animal Clinic | Assistant Appointments')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Assistant Panel')

@section('content')
<main class="admin-container">

  <div class="admin-page-heading">

    <div>

      <h1>
        Appointments
      </h1>

      <p>
        View and manage scheduled pet appointments.
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <div class="admin-tools">

    <input
      type="search"
      placeholder="Search by pet owner, pet name, or service..."
    >

    <select>

      <option>
        All Status
      </option>

      <option>
        Pending
      </option>

      <option>
        Confirmed
      </option>

      <option>
        Completed
      </option>

      <option>
        Cancelled
      </option>

    </select>

    <select>

      <option>
        All Services
      </option>

      <option>
        Consultation
      </option>

      <option>
        Vaccination
      </option>

      <option>
        Grooming
      </option>

    </select>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <div>

        <h2>
          Appointment List
        </h2>

        <p>
          Review appointment schedules and update appointment status.
        </p>

      </div>

    </div>

    <table class="admin-table">

      <thead>

        <tr>

          <th>
            Pet Owner
          </th>

          <th>
            Pet
          </th>

          <th>
            Service
          </th>

          <th>
            Date
          </th>

          <th>
            Time
          </th>

          <th>
            Status
          </th>

          <th>
            Actions
          </th>

        </tr>

      </thead>

      <tbody>

        <tr>

          <td>
            Mark Santos
          </td>

          <td>
            Max
          </td>

          <td>
            Consultation
          </td>

          <td>
            August 12, 2026
          </td>

          <td>
            10:00 AM
          </td>

          <td>

            <span class="status pending">
              Pending
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
              onclick="window.location.href='{{ route('staff.appointments.show', 1) }}'"
            >
              View
            </button>

          </td>

        </tr>

        <tr>

          <td>
            John Cruz
          </td>

          <td>
            Buddy
          </td>

          <td>
            Vaccination
          </td>

          <td>
            August 12, 2026
          </td>

          <td>
            11:30 AM
          </td>

          <td>

            <span class="status confirmed">
              Confirmed
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
              onclick="window.location.href='{{ route('staff.appointments.show', 1) }}'"
            >
              View
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Anna Reyes
          </td>

          <td>
            Coco
          </td>

          <td>
            Grooming
          </td>

          <td>
            August 13, 2026
          </td>

          <td>
            1:00 PM
          </td>

          <td>

            <span class="status pending">
              Pending
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
              onclick="window.location.href='{{ route('staff.appointments.show', 1) }}'"
            >
              View
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Mark Santos
          </td>

          <td>
            Luna
          </td>

          <td>
            Consultation
          </td>

          <td>
            August 13, 2026
          </td>

          <td>
            2:30 PM
          </td>

          <td>

            <span class="status confirmed">
              Confirmed
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
              onclick="window.location.href='{{ route('staff.appointments.show', 1) }}'"
            >
              View
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Sarah Garcia
          </td>

          <td>
            Milo
          </td>

          <td>
            Vaccination
          </td>

          <td>
            August 14, 2026
          </td>

          <td>
            9:30 AM
          </td>

          <td>

            <span class="status pending">
              Pending
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
              onclick="window.location.href='{{ route('staff.appointments.show', 1) }}'"
            >
              View
            </button>

          </td>

        </tr>

      </tbody>

    </table>

  </div>

</main>
@endsection
