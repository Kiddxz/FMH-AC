@extends('layouts.admin')

@section('title', 'FMH Animal Clinic | Admin Dashboard')
@section('body_class', 'admin-dashboard-page')
@section('header_class', 'admin-dashboard-header')
@section('nav_class', 'admin-dashboard-nav')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')

@section('content')
<main class="admin-dashboard-container">

  <div class="admin-dashboard-heading">

    <div>

      <h1>
        Admin Dashboard
      </h1>

      <p>
        Welcome back, Admin!
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <section class="admin-summary">

    <div class="admin-summary-card">

      <div class="admin-summary-icon">
        📅
      </div>

      <div>

        <h3>
          12
        </h3>

        <p>
          Today's Appointments
        </p>

      </div>

    </div>

    <div class="admin-summary-card">

      <div class="admin-summary-icon">
        🐾
      </div>

      <div>

        <h3>
          48
        </h3>

        <p>
          Registered Pets
        </p>

      </div>

    </div>

    <div class="admin-summary-card">

      <div class="admin-summary-icon">
        👤
      </div>

      <div>

        <h3>
          35
        </h3>

        <p>
          Pet Owners
        </p>

      </div>

    </div>

    <div class="admin-summary-card">

      <div class="admin-summary-icon">
        ⏳
      </div>

      <div>

        <h3>
          5
        </h3>

        <p>
          Pending Appointments
        </p>

      </div>

    </div>

  </section>

  <section class="admin-dashboard-content">

    <div class="admin-panel">

      <div class="admin-panel-header">

        <h2>
          Recent Appointments
        </h2>

        <a href="{{ route('admin.appointments.index') }}">
          View All
        </a>

      </div>

      <div class="admin-table-container">

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
                August 12, 2026
              </td>

              <td>

                <span class="status pending">
                  Pending
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
                August 12, 2026
              </td>

              <td>

                <span class="status confirmed">
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
                August 13, 2026
              </td>

              <td>

                <span class="status pending">
                  Pending
                </span>

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

                <span class="status completed">
                  Completed
                </span>

              </td>

            </tr>

          </tbody>

        </table>

      </div>

    </div>

    <div class="admin-panel">

      <div class="admin-panel-header">

        <h2>
          Quick Actions
        </h2>

      </div>

      <div class="admin-quick-actions">

        <a
          href="{{ route('admin.appointments.index') }}"
          class="admin-action"
        >

          <div class="admin-action-icon">
            📅
          </div>

          <div>

            <h3>
              Manage Appointments
            </h3>

            <p>
              View and manage pet appointments.
            </p>

          </div>

        </a>

        <a
          href="{{ route('admin.pets.index') }}"
          class="admin-action"
        >

          <div class="admin-action-icon">
            🐾
          </div>

          <div>

            <h3>
              Manage Pets
            </h3>

            <p>
              View registered pets and their records.
            </p>

          </div>

        </a>

        <a
          href="{{ route('admin.customers.index') }}"
          class="admin-action"
        >

          <div class="admin-action-icon">
            👤
          </div>

          <div>

            <h3>
              Manage Users
            </h3>

            <p>
              View and manage pet owner accounts.
            </p>

          </div>

        </a>

        <a
          href="{{ route('admin.services.index') }}"
          class="admin-action"
        >

          <div class="admin-action-icon">
            🩺
          </div>

          <div>

            <h3>
              Manage Services
            </h3>

            <p>
              Manage clinic services and information.
            </p>

          </div>

        </a>

        <a
          href="{{ route('admin.transactions.index') }}"
          class="admin-action"
        >

          <div class="admin-action-icon">
            💰
          </div>

          <div>

            <h3>
              Manage Payments
            </h3>

            <p>
              View and manage customer payments.
            </p>

          </div>

        </a>

        <a
          href="{{ route('admin.inventory.index') }}"
          class="admin-action"
        >

          <div class="admin-action-icon">
            📦
          </div>

          <div>

            <h3>
              Manage Inventory
            </h3>

            <p>
              Monitor clinic supplies and stock levels.
            </p>

          </div>

        </a>

        <a
          href="{{ route('admin.reports.index') }}"
          class="admin-action"
        >

          <div class="admin-action-icon">
            📊
          </div>

          <div>

            <h3>
              View Reports
            </h3>

            <p>
              View appointment, revenue, and pet reports.
            </p>

          </div>

        </a>

      </div>

    </div>

  </section>

</main>
@endsection
