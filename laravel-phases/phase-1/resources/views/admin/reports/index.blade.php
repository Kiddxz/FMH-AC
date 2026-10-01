@extends('layouts.admin')

@section('title', 'FMH Animal Clinic | Reports')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')

@section('content')
<main class="admin-container">

  <div class="admin-page-heading">

    <div>

      <h1>
        Reports
      </h1>

      <p>
        View clinic performance, appointment, payment, and pet statistics.
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <div class="admin-tools">

    <select>

      <option>
        August 2026
      </option>

      <option>
        July 2026
      </option>

      <option>
        June 2026
      </option>

      <option>
        May 2026
      </option>

    </select>

    <select>

      <option>
        This Month
      </option>

      <option>
        This Week
      </option>

      <option>
        This Year
      </option>

    </select>

    <button
      class="admin-add-btn"
      type="button"
    >
      Generate Report
    </button>

  </div>

  <div class="admin-stats">

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        📅
      </div>

      <div>

        <span>
          Total Appointments
        </span>

        <strong>
          86
        </strong>

      </div>

    </div>

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        ✅
      </div>

      <div>

        <span>
          Completed
        </span>

        <strong>
          68
        </strong>

      </div>

    </div>

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        ❌
      </div>

      <div>

        <span>
          Cancelled
        </span>

        <strong>
          9
        </strong>

      </div>

    </div>

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        💰
      </div>

      <div>

        <span>
          Total Revenue
        </span>

        <strong>
          ₱18,500
        </strong>

      </div>

    </div>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <div>

        <h2>
          Appointment Summary
        </h2>

        <p>
          Summary of appointments recorded during the selected period.
        </p>

      </div>

    </div>

    <table class="admin-table">

      <thead>

        <tr>

          <th>
            Service
          </th>

          <th>
            Total Appointments
          </th>

          <th>
            Completed
          </th>

          <th>
            Pending
          </th>

          <th>
            Cancelled
          </th>

        </tr>

      </thead>

      <tbody>

        <tr>

          <td>
            Consultation
          </td>

          <td>
            42
          </td>

          <td>
            34
          </td>

          <td>
            4
          </td>

          <td>
            4
          </td>

        </tr>

        <tr>

          <td>
            Vaccination
          </td>

          <td>
            27
          </td>

          <td>
            22
          </td>

          <td>
            3
          </td>

          <td>
            2
          </td>

        </tr>

        <tr>

          <td>
            Grooming
          </td>

          <td>
            17
          </td>

          <td>
            12
          </td>

          <td>
            3
          </td>

          <td>
            2
          </td>

        </tr>

      </tbody>

    </table>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <div>

        <h2>
          Revenue Summary
        </h2>

        <p>
          Payment and revenue summary for the selected period.
        </p>

      </div>

    </div>

    <table class="admin-table">

      <thead>

        <tr>

          <th>
            Service
          </th>

          <th>
            Transactions
          </th>

          <th>
            Paid
          </th>

          <th>
            Pending
          </th>

          <th>
            Revenue
          </th>

        </tr>

      </thead>

      <tbody>

        <tr>

          <td>
            Consultation
          </td>

          <td>
            42
          </td>

          <td>
            38
          </td>

          <td>
            4
          </td>

          <td>
            ₱8,400
          </td>

        </tr>

        <tr>

          <td>
            Vaccination
          </td>

          <td>
            27
          </td>

          <td>
            23
          </td>

          <td>
            4
          </td>

          <td>
            ₱7,800
          </td>

        </tr>

        <tr>

          <td>
            Grooming
          </td>

          <td>
            17
          </td>

          <td>
            15
          </td>

          <td>
            2
          </td>

          <td>
            ₱2,300
          </td>

        </tr>

      </tbody>

    </table>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <div>

        <h2>
          Pet Statistics
        </h2>

        <p>
          Overview of registered pets in the clinic.
        </p>

      </div>

    </div>

    <table class="admin-table">

      <thead>

        <tr>

          <th>
            Species
          </th>

          <th>
            Registered Pets
          </th>

          <th>
            Percentage
          </th>

        </tr>

      </thead>

      <tbody>

        <tr>

          <td>
            Dog
          </td>

          <td>
            28
          </td>

          <td>
            67%
          </td>

        </tr>

        <tr>

          <td>
            Cat
          </td>

          <td>
            11
          </td>

          <td>
            26%
          </td>

        </tr>

        <tr>

          <td>
            Bird
          </td>

          <td>
            2
          </td>

          <td>
            5%
          </td>

        </tr>

        <tr>

          <td>
            Rabbit
          </td>

          <td>
            1
          </td>

          <td>
            2%
          </td>

        </tr>

      </tbody>

    </table>

  </div>

</main>
@endsection
