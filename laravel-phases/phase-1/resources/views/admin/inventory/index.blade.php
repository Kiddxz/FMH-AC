@extends('layouts.admin')

@section('title', 'FMH Animal Clinic | Inventory')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')

@section('content')
<main class="admin-container">

  <div class="admin-page-heading">

    <div>

      <h1>
        Inventory
      </h1>

      <p>
        Manage and monitor clinic supplies and available stock.
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <div class="admin-stats">

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        📦
      </div>

      <div>

        <span>
          Total Items
        </span>

        <strong>
          48
        </strong>

      </div>

    </div>

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        ✅
      </div>

      <div>

        <span>
          Available Items
        </span>

        <strong>
          39
        </strong>

      </div>

    </div>

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        ⚠️
      </div>

      <div>

        <span>
          Low Stock
        </span>

        <strong>
          6
        </strong>

      </div>

    </div>

    <div class="admin-stat-card">

      <div class="admin-stat-icon">
        ❌
      </div>

      <div>

        <span>
          Out of Stock
        </span>

        <strong>
          3
        </strong>

      </div>

    </div>

  </div>

  <div class="admin-tools">

    <input
      type="search"
      placeholder="Search inventory item..."
    >

    <select>

      <option>
        All Categories
      </option>

      <option>
        Medical Supplies
      </option>

      <option>
        Vaccines
      </option>

      <option>
        Grooming Supplies
      </option>

      <option>
        Cleaning Supplies
      </option>

      <option>
        Other
      </option>

    </select>

    <select>

      <option>
        All Stock Status
      </option>

      <option>
        Available
      </option>

      <option>
        Low Stock
      </option>

      <option>
        Out of Stock
      </option>

    </select>

    <button
      class="admin-add-btn"
      type="button"
    >
      + Add Item
    </button>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <div>

        <h2>
          Inventory Items
        </h2>

        <p>
          View and manage clinic supplies and stock levels.
        </p>

      </div>

    </div>

    <table class="admin-table">

      <thead>

        <tr>

          <th>
            Item
          </th>

          <th>
            Category
          </th>

          <th>
            Quantity
          </th>

          <th>
            Unit
          </th>

          <th>
            Minimum Stock
          </th>

          <th>
            Last Updated
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
            Disposable Syringes
          </td>

          <td>
            Medical Supplies
          </td>

          <td>
            120
          </td>

          <td>
            pieces
          </td>

          <td>
            50
          </td>

          <td>
            August 12, 2026
          </td>

          <td>

            <span class="status completed">
              Available
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
            >
              View
            </button>

            <button
              class="action-edit"
              type="button"
            >
              Edit
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Rabies Vaccine
          </td>

          <td>
            Vaccines
          </td>

          <td>
            8
          </td>

          <td>
            doses
          </td>

          <td>
            10
          </td>

          <td>
            August 12, 2026
          </td>

          <td>

            <span class="status pending">
              Low Stock
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
            >
              View
            </button>

            <button
              class="action-edit"
              type="button"
            >
              Edit
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Pet Shampoo
          </td>

          <td>
            Grooming Supplies
          </td>

          <td>
            25
          </td>

          <td>
            bottles
          </td>

          <td>
            10
          </td>

          <td>
            August 11, 2026
          </td>

          <td>

            <span class="status completed">
              Available
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
            >
              View
            </button>

            <button
              class="action-edit"
              type="button"
            >
              Edit
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Surgical Gloves
          </td>

          <td>
            Medical Supplies
          </td>

          <td>
            7
          </td>

          <td>
            boxes
          </td>

          <td>
            10
          </td>

          <td>
            August 10, 2026
          </td>

          <td>

            <span class="status pending">
              Low Stock
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
            >
              View
            </button>

            <button
              class="action-edit"
              type="button"
            >
              Edit
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Cotton Balls
          </td>

          <td>
            Medical Supplies
          </td>

          <td>
            0
          </td>

          <td>
            packs
          </td>

          <td>
            15
          </td>

          <td>
            August 09, 2026
          </td>

          <td>

            <span class="status cancelled">
              Out of Stock
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
            >
              View
            </button>

            <button
              class="action-edit"
              type="button"
            >
              Edit
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Disinfectant Solution
          </td>

          <td>
            Cleaning Supplies
          </td>

          <td>
            18
          </td>

          <td>
            bottles
          </td>

          <td>
            8
          </td>

          <td>
            August 08, 2026
          </td>

          <td>

            <span class="status completed">
              Available
            </span>

          </td>

          <td>

            <button
              class="action-view"
              type="button"
            >
              View
            </button>

            <button
              class="action-edit"
              type="button"
            >
              Edit
            </button>

          </td>

        </tr>

      </tbody>

    </table>

  </div>

</main>
@endsection
