@extends('layouts.superadmin')

@section('title', 'FMH Animal Clinic | Super Admin Appointments')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')

@section('content')
<main class="superadmin-container">

  <div class="superadmin-page-heading">

    <div>

      <h1>
        Appointment Lists
      </h1>

      <p>
        Monitor clinic appointments and schedules.
      </p>

    </div>

  </div>

  <div class="superadmin-tools">

    <input
      type="search"
      placeholder="Search appointment..."
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

  </div>

  <div class="superadmin-table-card">

    <table class="superadmin-table">

      <thead>

        <tr>

          <th>
            Customer
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
            September 29, 2026
          </td>

          <td>
            9:00 AM
          </td>

          <td>

            <span class="status active">
              Confirmed
            </span>

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
            September 29, 2026
          </td>

          <td>
            10:00 AM
          </td>

          <td>

            <span class="status active">
              Confirmed
            </span>

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
            September 30, 2026
          </td>

          <td>
            2:00 PM
          </td>

          <td>

            <span class="status inactive">
              Pending
            </span>

          </td>

        </tr>

      </tbody>

    </table>

  </div>

</main>
@endsection
