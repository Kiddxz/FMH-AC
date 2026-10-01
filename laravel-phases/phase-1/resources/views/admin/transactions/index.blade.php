@extends('layouts.admin')

@section('title', 'FMH Animal Clinic | Payments')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')

@section('content')
<main class="admin-container">

  <div class="admin-page-heading">

    <div>

      <h1>
        Payments
      </h1>

      <p>
        Manage and monitor all customer payment transactions.
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <div class="admin-stats">

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

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        ✅
      </div>

      <div>

        <span>
          Paid Transactions
        </span>

        <strong>
          24
        </strong>

      </div>

    </div>

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        ⏳
      </div>

      <div>

        <span>
          Pending Payments
        </span>

        <strong>
          5
        </strong>

      </div>

    </div>

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        ❌
      </div>

      <div>

        <span>
          Cancelled Payments
        </span>

        <strong>
          2
        </strong>

      </div>

    </div>

  </div>

  <div class="admin-tools">

    <input
      type="search"
      placeholder="Search by customer, pet, or payment ID..."
    >

    <select>

      <option>
        All Payment Status
      </option>

      <option>
        Paid
      </option>

      <option>
        Pending
      </option>

      <option>
        Cancelled
      </option>

    </select>

    <select>

      <option>
        All Payment Methods
      </option>

      <option>
        Cash
      </option>

      <option>
        GCash
      </option>

      <option>
        Maya
      </option>

      <option>
        Credit Card
      </option>

      <option>
        Debit Card
      </option>

    </select>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <div>

        <h2>
          Payment Transactions
        </h2>

        <p>
          View and monitor all recorded customer payments.
        </p>

      </div>

    </div>

    <table class="admin-table">

      <thead>

        <tr>

          <th>
            Payment ID
          </th>

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
            Amount
          </th>

          <th>
            Payment Method
          </th>

          <th>
            Date
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
            PAY-001
          </td>

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
            ₱800
          </td>

          <td>
            Cash
          </td>

          <td>
            August 12, 2026
          </td>

          <td>

            <span class="status completed">
              Paid
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
            >
              View
            </button>

          </td>

        </tr>

        <tr>

          <td>
            PAY-002
          </td>

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
            ₱1,200
          </td>

          <td>
            GCash
          </td>

          <td>
            August 12, 2026
          </td>

          <td>

            <span class="status completed">
              Paid
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
            >
              View
            </button>

          </td>

        </tr>

        <tr>

          <td>
            PAY-003
          </td>

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
            ₱700
          </td>

          <td>
            Maya
          </td>

          <td>
            August 13, 2026
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
            >
              View
            </button>

          </td>

        </tr>

        <tr>

          <td>
            PAY-004
          </td>

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
            ₱800
          </td>

          <td>
            Cash
          </td>

          <td>
            August 13, 2026
          </td>

          <td>

            <span class="status completed">
              Paid
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
            >
              View
            </button>

          </td>

        </tr>

        <tr>

          <td>
            PAY-005
          </td>

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
            ₱1,200
          </td>

          <td>
            Credit Card
          </td>

          <td>
            August 14, 2026
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
            >
              View
            </button>

          </td>

        </tr>

        <tr>

          <td>
            PAY-006
          </td>

          <td>
            James Garcia
          </td>

          <td>
            Rocky
          </td>

          <td>
            Grooming
          </td>

          <td>
            ₱700
          </td>

          <td>
            GCash
          </td>

          <td>
            August 14, 2026
          </td>

          <td>

            <span class="status cancelled">
              Cancelled
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
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
