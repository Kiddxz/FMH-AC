@extends('layouts.admin')

@section('title', 'FMH Animal Clinic | Admin Profile')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')

@section('content')
<main class="admin-container">

  <div class="admin-page-heading">

    <div>

      <h1>
        Admin Profile
      </h1>

      <p>
        View and manage your administrator account information.
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <h2>
        Account Information
      </h2>

    </div>

    <table class="admin-table">

      <tbody>

        <tr>

          <th>
            Full Name
          </th>

          <td>
            Admin User
          </td>

        </tr>

        <tr>

          <th>
            Email
          </th>

          <td>
            admin@fmhanimalclinic.com
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
            Role
          </th>

          <td>
            Administrator
          </td>

        </tr>

        <tr>

          <th>
            Account Status
          </th>

          <td>

            <span class="user-status active">
              Active
            </span>

          </td>

        </tr>

        <tr>

          <th>
            Date Registered
          </th>

          <td>
            August 1, 2026
          </td>

        </tr>

        <tr>

          <th>
            Last Login
          </th>

          <td>
            August 12, 2026
          </td>

        </tr>

      </tbody>

    </table>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <h2>
        Account Settings
      </h2>

    </div>

    <div class="admin-tools">

      <button
        class="admin-add-btn"
        type="button"
      >
        Edit Profile
      </button>

      <button
        class="action-edit"
        type="button"
      >
        Change Password
      </button>

    </div>

  </div>

</main>
@endsection
