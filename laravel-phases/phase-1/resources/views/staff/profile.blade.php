@extends('layouts.staff')

@section('title', 'FMH Animal Clinic | Assistant Profile')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Assistant Panel')

@section('content')
<main class="admin-container">

  <div class="admin-page-heading">

    <div>

      <h1>
        My Profile
      </h1>

      <p>
        View and manage your assistant account information.
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <div>

        <h2>
          Account Information
        </h2>

        <p>
          Your personal and account details.
        </p>

      </div>

      <span class="status confirmed">
        Active
      </span>

    </div>

    <table class="admin-table">

      <tbody>

        <tr>

          <th>
            Full Name
          </th>

          <td>
            Ana Cruz
          </td>

        </tr>

        <tr>

          <th>
            Email Address
          </th>

          <td>
            ana@fmhanimalclinic.com
          </td>

        </tr>

        <tr>

          <th>
            Mobile Number
          </th>

          <td>
            09181234567
          </td>

        </tr>

        <tr>

          <th>
            Role
          </th>

          <td>
            Veterinary Assistant
          </td>

        </tr>

        <tr>

          <th>
            Account Status
          </th>

          <td>
            Active
          </td>

        </tr>

      </tbody>

    </table>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <div>

        <h2>
          Account Actions
        </h2>

        <p>
          Manage your account settings.
        </p>

      </div>

    </div>

    <div class="admin-tools">

      <button
        class="action-edit"
        type="button"
      >
        ✏️ Edit Profile
      </button>

      <button
        class="action-view"
        type="button"
      >
        🔒 Change Password
      </button>

    </div>

  </div>

  <div class="admin-tools">

    <button
      class="action-view"
      type="button"
      onclick="window.location.href='{{ route('staff.dashboard') }}'"
    >
      ← Back to Dashboard
    </button>

  </div>

</main>
@endsection
