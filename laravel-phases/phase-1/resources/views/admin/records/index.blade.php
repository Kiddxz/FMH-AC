@extends('layouts.admin')

@section('title', 'FMH Animal Clinic | Pet Records')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')

@section('content')
<main class="admin-container">

  <div class="admin-page-heading">

    <div>

      <h1>
        Pet Records
      </h1>

      <p>
        View detailed medical records and health information of registered pets.
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <div class="admin-tools">

    <input
      type="search"
      placeholder="Search by pet name or owner..."
    >

    <select>

      <option>
        All Species
      </option>

      <option>
        Dog
      </option>

      <option>
        Cat
      </option>

    </select>

    <button
      class="admin-add-btn"
      type="button"
    >
      + Add Pet Record
    </button>

  </div>

  <div class="admin-table-card">

    <table class="admin-table">

      <thead>

        <tr>

          <th>
            Pet Name
          </th>

          <th>
            Owner
          </th>

          <th>
            Species
          </th>

          <th>
            Breed
          </th>

          <th>
            Last Visit
          </th>

          <th>
            Medical Records
          </th>

          <th>
            Actions
          </th>

        </tr>

      </thead>

      <tbody>

        <tr>

          <td>
            Max
          </td>

          <td>
            Mark Santos
          </td>

          <td>
            Dog
          </td>

          <td>
            Golden Retriever
          </td>

          <td>
            August 12, 2026
          </td>

          <td>
            4 records
          </td>

          <td>

            <button class="action-view">
              View
            </button>

            <button class="action-edit">
              Edit
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Buddy
          </td>

          <td>
            John Cruz
          </td>

          <td>
            Dog
          </td>

          <td>
            Labrador Retriever
          </td>

          <td>
            August 12, 2026
          </td>

          <td>
            3 records
          </td>

          <td>

            <button class="action-view">
              View
            </button>

            <button class="action-edit">
              Edit
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Coco
          </td>

          <td>
            Anna Reyes
          </td>

          <td>
            Cat
          </td>

          <td>
            Persian
          </td>

          <td>
            August 10, 2026
          </td>

          <td>
            5 records
          </td>

          <td>

            <button class="action-view">
              View
            </button>

            <button class="action-edit">
              Edit
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Luna
          </td>

          <td>
            Mark Santos
          </td>

          <td>
            Cat
          </td>

          <td>
            Siamese
          </td>

          <td>
            August 8, 2026
          </td>

          <td>
            2 records
          </td>

          <td>

            <button class="action-view">
              View
            </button>

            <button class="action-edit">
              Edit
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Bella
          </td>

          <td>
            Maria Lopez
          </td>

          <td>
            Dog
          </td>

          <td>
            Shih Tzu
          </td>

          <td>
            August 7, 2026
          </td>

          <td>
            3 records
          </td>

          <td>

            <button class="action-view">
              View
            </button>

            <button class="action-edit">
              Edit
            </button>

          </td>

        </tr>

      </tbody>

    </table>

  </div>

</main>
@endsection
