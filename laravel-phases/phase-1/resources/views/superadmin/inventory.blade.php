@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Inventory')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Inventory Monitoring</h1>
      <p>Monitor medicines, vaccines, and clinic supplies.</p>
    </div>
  </div>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Item</th>
          <th>Category</th>
          <th>Quantity</th>
          <th>Expiration Date</th>
          <th>Minimum Stock</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Rabies Vaccine</td>
          <td>Vaccine</td>
          <td>8</td>
          <td>December 15, 2026</td>
          <td>10</td>
          <td>
            <span class="status inactive">Low Stock</span>
          </td>
        </tr>
        <tr>
          <td>Disposable Syringes</td>
          <td>Supplies</td>
          <td>120</td>
          <td>N/A</td>
          <td>30</td>
          <td>
            <span class="status active">Available</span>
          </td>
        </tr>
        <tr>
          <td>Pet Shampoo</td>
          <td>Supplies</td>
          <td>35</td>
          <td>March 20, 2027</td>
          <td>10</td>
          <td>
            <span class="status active">Available</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</main>
@endsection
