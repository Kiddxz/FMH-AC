@extends('layouts.admin')
@section('title', 'FMH Animal Clinic | Users')
@section('body_class', 'admin-dashboard-page')
@section('footer', '© 2026 FMH Animal Clinic | Admin Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Users</h1>
      <p>View and manage registered pet owners.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <div class="admin-tools">
    <input type="search" placeholder="Search by name or email...">
    <select>
      <option>All Status</option>
      <option>Active</option>
      <option>Inactive</option>
    </select>
    <button class="admin-add-btn" type="button">+ Add New User</button>
  </div>
  <div class="admin-table-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Mobile</th>
          <th>Number of Pets</th>
          <th>Registration Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Mark Santos</td>
          <td>marksantos@email.com</td>
          <td>09171234567</td>
          <td>2</td>
          <td>August 1, 2026</td>
          <td>
            <span class="user-status active">Active</span>
          </td>
          <td>
            <button class="action-view">View</button>
            <button class="action-edit">Edit</button>
            <button class="action-delete">Delete</button>
          </td>
        </tr>
        <tr>
          <td>John Cruz</td>
          <td>johncruz@email.com</td>
          <td>09181234567</td>
          <td>1</td>
          <td>August 3, 2026</td>
          <td>
            <span class="user-status active">Active</span>
          </td>
          <td>
            <button class="action-view">View</button>
            <button class="action-edit">Edit</button>
            <button class="action-delete">Delete</button>
          </td>
        </tr>
        <tr>
          <td>Anna Reyes</td>
          <td>annareyes@email.com</td>
          <td>09191234567</td>
          <td>1</td>
          <td>August 5, 2026</td>
          <td>
            <span class="user-status active">Active</span>
          </td>
          <td>
            <button class="action-view">View</button>
            <button class="action-edit">Edit</button>
            <button class="action-delete">Delete</button>
          </td>
        </tr>
        <tr>
          <td>Maria Lopez</td>
          <td>marialopez@email.com</td>
          <td>09201234567</td>
          <td>1</td>
          <td>August 7, 2026</td>
          <td>
            <span class="user-status inactive">Inactive</span>
          </td>
          <td>
            <button class="action-view">View</button>
            <button class="action-edit">Edit</button>
            <button class="action-delete">Delete</button>
          </td>
        </tr>
        <tr>
          <td>James Garcia</td>
          <td>jamesgarcia@email.com</td>
          <td>09211234567</td>
          <td>3</td>
          <td>August 9, 2026</td>
          <td>
            <span class="user-status active">Active</span>
          </td>
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
