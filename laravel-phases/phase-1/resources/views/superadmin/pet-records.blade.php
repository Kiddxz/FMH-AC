@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Pet Records')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Digital Pet Records</h1>
      <p>View and monitor digital pet records.</p>
    </div>
  </div>
  <div class="superadmin-tools">
    <input type="search" placeholder="Search pet or owner...">
    <select>
      <option>All Species</option>
      <option>Dog</option>
      <option>Cat</option>
    </select>
  </div>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Pet Name</th>
          <th>Owner</th>
          <th>Species</th>
          <th>Breed</th>
          <th>Medical History</th>
          <th>Vaccination</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Max</td>
          <td>Mark Santos</td>
          <td>Dog</td>
          <td>Labrador</td>
          <td>Available</td>
          <td>Updated</td>
        </tr>
        <tr>
          <td>Buddy</td>
          <td>John Cruz</td>
          <td>Dog</td>
          <td>Shih Tzu</td>
          <td>Available</td>
          <td>Updated</td>
        </tr>
        <tr>
          <td>Coco</td>
          <td>Anna Reyes</td>
          <td>Cat</td>
          <td>Persian</td>
          <td>Available</td>
          <td>Updated</td>
        </tr>
      </tbody>
    </table>
  </div>
</main>
@endsection
