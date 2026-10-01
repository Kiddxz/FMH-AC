@extends('layouts.staff')

@section('title', 'FMH Animal Clinic | Assistant Pets')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Assistant Panel')

@section('content')
<main class="admin-container">

  <div class="admin-page-heading">

    <div>

      <h1>
        Registered Pets
      </h1>

      <p>
        View registered pets and their owner information.
      </p>

    </div>

    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>

  </div>

  <div class="admin-tools">

    <input
      type="search"
      placeholder="Search by pet name, breed, or owner..."
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

      <option>
        Bird
      </option>

      <option>
        Rabbit
      </option>

      <option>
        Other
      </option>

    </select>

  </div>

  <div class="admin-table-card">

    <div class="admin-panel-header">

      <div>

        <h2>
          Pet List
        </h2>

        <p>
          Select a pet to view its complete pet record.
        </p>

      </div>

    </div>

    <table class="admin-table">

      <thead>

        <tr>

          <th>
            Pet Name
          </th>

          <th>
            Species
          </th>

          <th>
            Breed
          </th>

          <th>
            Sex
          </th>

          <th>
            Age
          </th>

          <th>
            Owner
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
            Dog
          </td>

          <td>
            Golden Retriever
          </td>

          <td>
            Male
          </td>

          <td>
            3 years old
          </td>

          <td>
            Mark Santos
          </td>

          <td>

            <button
              class="action-view"
              type="button"
              onclick="window.location.href='{{ route('staff.pets.show', 1) }}'"
            >
              View Record
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Buddy
          </td>

          <td>
            Dog
          </td>

          <td>
            Labrador Retriever
          </td>

          <td>
            Male
          </td>

          <td>
            2 years old
          </td>

          <td>
            John Cruz
          </td>

          <td>

            <button
              class="action-view"
              type="button"
              onclick="window.location.href='{{ route('staff.pets.show', 1) }}'"
            >
              View Record
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Coco
          </td>

          <td>
            Cat
          </td>

          <td>
            Persian
          </td>

          <td>
            Female
          </td>

          <td>
            4 years old
          </td>

          <td>
            Anna Reyes
          </td>

          <td>

            <button
              class="action-view"
              type="button"
              onclick="window.location.href='{{ route('staff.pets.show', 1) }}'"
            >
              View Record
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Luna
          </td>

          <td>
            Dog
          </td>

          <td>
            Shih Tzu
          </td>

          <td>
            Female
          </td>

          <td>
            5 years old
          </td>

          <td>
            Mark Santos
          </td>

          <td>

            <button
              class="action-view"
              type="button"
              onclick="window.location.href='{{ route('staff.pets.show', 1) }}'"
            >
              View Record
            </button>

          </td>

        </tr>

        <tr>

          <td>
            Milo
          </td>

          <td>
            Cat
          </td>

          <td>
            Siamese
          </td>

          <td>
            Male
          </td>

          <td>
            1 year old
          </td>

          <td>
            Sarah Garcia
          </td>

          <td>

            <button
              class="action-view"
              type="button"
              onclick="window.location.href='{{ route('staff.pets.show', 1) }}'"
            >
              View Record
            </button>

          </td>

        </tr>

      </tbody>

    </table>

  </div>

</main>
@endsection
