@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Reports')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Reports & Analytics</h1>
      <p>Monitor appointments, inventory, transactions, and pet records.</p>
    </div>
  </div>
  <div class="superadmin-tools">
    <select>
      <option>Appointment Report</option>
      <option>Inventory Report</option>
      <option>Transaction Report</option>
      <option>Pet Record Summary</option>
      <option>Patient Flow Report</option>
    </select>
    <input type="date">
    <input type="date">
    <button type="button" class="superadmin-add-btn">Generate Report</button>
  </div>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Report</th>
          <th>Records</th>
          <th>Date Generated</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Appointment Report</td>
          <td>86</td>
          <td>September 29, 2026</td>
          <td>
            <span class="status active">Available</span>
          </td>
        </tr>
        <tr>
          <td>Inventory Report</td>
          <td>48</td>
          <td>September 29, 2026</td>
          <td>
            <span class="status active">Available</span>
          </td>
        </tr>
        <tr>
          <td>Transaction Report</td>
          <td>86</td>
          <td>September 29, 2026</td>
          <td>
            <span class="status active">Available</span>
          </td>
        </tr>
        <tr>
          <td>Pet Record Summary</td>
          <td>48</td>
          <td>September 29, 2026</td>
          <td>
            <span class="status active">Available</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</main>
@endsection
