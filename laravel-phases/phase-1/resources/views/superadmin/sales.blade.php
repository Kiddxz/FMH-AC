@extends('layouts.superadmin')

@section('title', 'FMH Animal Clinic | Super Admin POS')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')

@section('content')
<main class="superadmin-container">

  <div class="superadmin-page-heading">

    <div>

      <h1>
        Point of Sale
      </h1>

      <p>
        Monitor clinic sales and payment transactions.
      </p>

    </div>

  </div>

  <div class="superadmin-tools">

    <input
      type="search"
      placeholder="Search transaction..."
    >

    <select>

      <option>
        All Payment Types
      </option>

      <option>
        Cash
      </option>

      <option>
        Card
      </option>

    </select>

  </div>

  <div class="superadmin-table-card">

    <table class="superadmin-table">

      <thead>

        <tr>

          <th>
            Transaction ID
          </th>

          <th>
            Customer
          </th>

          <th>
            Service
          </th>

          <th>
            Amount
          </th>

          <th>
            Payment Method
          </th>

          <th>
            Date
          </th>

        </tr>

      </thead>

      <tbody>

        <tr>

          <td>
            TXN-001
          </td>

          <td>
            Mark Santos
          </td>

          <td>
            Consultation
          </td>

          <td>
            ₱500
          </td>

          <td>
            Cash
          </td>

          <td>
            September 29, 2026
          </td>

        </tr>

        <tr>

          <td>
            TXN-002
          </td>

          <td>
            John Cruz
          </td>

          <td>
            Vaccination
          </td>

          <td>
            ₱800
          </td>

          <td>
            Cash
          </td>

          <td>
            September 29, 2026
          </td>

        </tr>

      </tbody>

    </table>

  </div>

</main>
@endsection
