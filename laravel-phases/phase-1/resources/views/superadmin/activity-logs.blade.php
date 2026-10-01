@extends('layouts.superadmin')

@section('title', 'FMH Animal Clinic | Activity Logs')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')

@section('content')
<main class="superadmin-container">

  <div class="superadmin-page-heading">

    <div>

      <h1>
        System Activity Logs
      </h1>

      <p>
        Monitor important activities performed in the system.
      </p>

    </div>

  </div>

  <div class="superadmin-table-card">

    <table class="superadmin-table">

      <thead>

        <tr>

          <th>
            User
          </th>

          <th>
            Activity
          </th>

          <th>
            Module
          </th>

          <th>
            Date
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
            Logged in
          </td>

          <td>
            Login
          </td>

          <td>
            September 29, 2026
          </td>

          <td>
            Successful
          </td>

        </tr>

        <tr>

          <td>
            Anna Reyes
          </td>

          <td>
            Updated appointment
          </td>

          <td>
            Appointments
          </td>

          <td>
            September 29, 2026
          </td>

          <td>
            Successful
          </td>

        </tr>

        <tr>

          <td>
            Staff
          </td>

          <td>
            Updated inventory
          </td>

          <td>
            Inventory
          </td>

          <td>
            September 29, 2026
          </td>

          <td>
            Successful
          </td>

        </tr>

        <tr>

          <td>
            Staff
          </td>

          <td>
            Recorded transaction
          </td>

          <td>
            POS
          </td>

          <td>
            September 29, 2026
          </td>

          <td>
            Successful
          </td>

        </tr>

      </tbody>

    </table>

  </div>

</main>
@endsection
