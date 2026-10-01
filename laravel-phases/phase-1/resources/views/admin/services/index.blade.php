@extends('layouts.admin')

@section('title', 'FMH Animal Clinic | Services')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')

@section('content')
<main class="admin-container">

  <div class="admin-page-heading">

    <div>

      <h1>
        Services
      </h1>

      <p>
        View and manage clinic services.
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <div class="admin-tools">

    <input
      type="search"
      placeholder="Search service..."
    >

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

    <button
      class="admin-add-btn"
      type="button"
    >
      + Add New Service
    </button>

  </div>

  <div class="admin-table-card">

    <table class="admin-table">

      <thead>

        <tr>

          <th>
            Service Name
          </th>

          <th>
            Description
          </th>

          <th>
            Price
          </th>

          <th>
            Duration
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
            Consultation
          </td>

          <td>
            General veterinary consultation and health assessment.
          </td>

          <td>
            ₱500
          </td>

          <td>
            30 minutes
          </td>

          <td>

            <span class="user-status active">
              Active
            </span>

          </td>

          <td>

            <button class="action-view">
              View
            </button>

            <button class="action-edit">
              Edit
            </button>

            <button class="action-delete">
              Delete
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Vaccination
          </td>

          <td>
            Vaccination service to help protect pets from common diseases.
          </td>

          <td>
            ₱800
          </td>

          <td>
            20 minutes
          </td>

          <td>

            <span class="user-status active">
              Active
            </span>

          </td>

          <td>

            <button class="action-view">
              View
            </button>

            <button class="action-edit">
              Edit
            </button>

            <button class="action-delete">
              Delete
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Grooming
          </td>

          <td>
            Professional grooming service for dogs and cats.
          </td>

          <td>
            ₱600
          </td>

          <td>
            1 hour
          </td>

          <td>

            <span class="user-status active">
              Active
            </span>

          </td>

          <td>

            <button class="action-view">
              View
            </button>

            <button class="action-edit">
              Edit
            </button>

            <button class="action-delete">
              Delete
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Deworming
          </td>

          <td>
            Deworming treatment to help maintain your pet's health.
          </td>

          <td>
            ₱350
          </td>

          <td>
            15 minutes
          </td>

          <td>

            <span class="user-status active">
              Active
            </span>

          </td>

          <td>

            <button class="action-view">
              View
            </button>

            <button class="action-edit">
              Edit
            </button>

            <button class="action-delete">
              Delete
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Nail Trimming
          </td>

          <td>
            Basic nail trimming and paw care for pets.
          </td>

          <td>
            ₱250
          </td>

          <td>
            15 minutes
          </td>

          <td>

            <span class="user-status inactive">
              Inactive
            </span>

          </td>

          <td>

            <button class="action-view">
              View
            </button>

            <button class="action-edit">
              Edit
            </button>

            <button class="action-delete">
              Delete
            </button>

          </td>

        </tr>

      </tbody>

    </table>

  </div>

</main>
@endsection
