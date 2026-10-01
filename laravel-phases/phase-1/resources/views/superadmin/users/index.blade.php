@extends('layouts.superadmin')

@section('title', 'FMH Animal Clinic | Super Admin Users')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')

@section('content')
<main class="superadmin-container">

  <div class="superadmin-page-heading">

    <div>

      <h1>
        User Management
      </h1>

      <p>
        Manage user accounts, roles, and permissions.
      </p>

    </div>

    <button
      type="button"
      class="superadmin-add-btn"
    >
      + Add User
    </button>

  </div>

  <div class="superadmin-tools">

    <input
      type="search"
      placeholder="Search user..."
    >

    <select>

      <option>
        All Roles
      </option>

      <option>
        Super Administrator
      </option>

      <option>
        Veterinarian/Administrator
      </option>

      <option>
        Staff/Receptionist
      </option>

      <option>
        Customer
      </option>

    </select>

    <select>

      <option>
        All Status
      </option>

      <option>
        Active
      </option>

      <option>
        Inactive
      </option>

    </select>

  </div>

  <div class="superadmin-table-card">

    <table class="superadmin-table">

      <thead>

        <tr>

          <th>
            Name
          </th>

          <th>
            Email
          </th>

          <th>
            Role
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
            System Administrator
          </td>

          <td>
            superadmin@fmhanimalclinic.com
          </td>

          <td>
            Super Administrator
          </td>

          <td>

            <span class="status active">
              Active
            </span>

          </td>

          <td>

            <button
              type="button"
              class="action-edit"
            >
              Edit
            </button>

            <button
              type="button"
              class="action-delete"
            >
              Deactivate
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Mark Santos
          </td>

          <td>
            mark@email.com
          </td>

          <td>
            Veterinarian/Administrator
          </td>

          <td>

            <span class="status active">
              Active
            </span>

          </td>

          <td>

            <button
              type="button"
              class="action-edit"
            >
              Edit
            </button>

            <button
              type="button"
              class="action-delete"
            >
              Deactivate
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Anna Reyes
          </td>

          <td>
            anna@email.com
          </td>

          <td>
            Staff/Receptionist
          </td>

          <td>

            <span class="status active">
              Active
            </span>

          </td>

          <td>

            <button
              type="button"
              class="action-edit"
            >
              Edit
            </button>

            <button
              type="button"
              class="action-delete"
            >
              Deactivate
            </button>

          </td>

        </tr>

        <tr>

          <td>
            John Cruz
          </td>

          <td>
            john@email.com
          </td>

          <td>
            Customer
          </td>

          <td>

            <span class="status inactive">
              Inactive
            </span>

          </td>

          <td>

            <button
              type="button"
              class="action-edit"
            >
              Edit
            </button>

            <button
              type="button"
              class="action-delete"
            >
              Activate
            </button>

          </td>

        </tr>

      </tbody>

    </table>

  </div>

</main>
@endsection
