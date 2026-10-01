@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Pets')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Pets</h1>
      <p>View and manage registered pets.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-tools">
    <input type="search" placeholder="Search by pet name, breed, or owner...">
    <select>
      <option>All Species</option>
      <option>Dog</option>
      <option>Cat</option>
    </select>
    <select>
      <option>All Gender</option>
      <option>Male</option>
      <option>Female</option>
    </select>
    <button class="admin-add-btn" type="button">+ Add New Pet</button>
  </div>
  <div class="admin-table-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Pet Name</th>
          <th>Breed</th>
          <th>Species</th>
          <th>Gender</th>
          <th>Age</th>
          <th>Owner</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Max</td>
          <td>Golden Retriever</td>
          <td>Dog</td>
          <td>Male</td>
          <td>3 years</td>
          <td>Mark Santos</td>
          <td>
            <button class="action-view">View</button>
            <button class="action-edit">Edit</button>
            <button class="action-delete">Delete</button>
          </td>
        </tr>
        <tr>
          <td>Buddy</td>
          <td>Labrador Retriever</td>
          <td>Dog</td>
          <td>Male</td>
          <td>2 years</td>
          <td>John Cruz</td>
          <td>
            <button class="action-view">View</button>
            <button class="action-edit">Edit</button>
            <button class="action-delete">Delete</button>
          </td>
        </tr>
        <tr>
          <td>Coco</td>
          <td>Persian</td>
          <td>Cat</td>
          <td>Female</td>
          <td>2 years</td>
          <td>Anna Reyes</td>
          <td>
            <button class="action-view">View</button>
            <button class="action-edit">Edit</button>
            <button class="action-delete">Delete</button>
          </td>
        </tr>
        <tr>
          <td>Luna</td>
          <td>Siamese</td>
          <td>Cat</td>
          <td>Female</td>
          <td>1 year</td>
          <td>Mark Santos</td>
          <td>
            <button class="action-view">View</button>
            <button class="action-edit">Edit</button>
            <button class="action-delete">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</main>
@endsection
