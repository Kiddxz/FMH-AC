@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Transactions')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Sales & Transaction Records</h1>
      <p>Monitor recorded clinic transactions.</p>
    </div>
  </div>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Transaction ID</th>
          <th>Customer</th>
          <th>Pet</th>
          <th>Service</th>
          <th>Amount</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>TXN-001</td>
          <td>Mark Santos</td>
          <td>Max</td>
          <td>Consultation</td>
          <td>₱500</td>
          <td>September 29, 2026</td>
          <td>
            <span class="status active">Paid</span>
          </td>
        </tr>
        <tr>
          <td>TXN-002</td>
          <td>John Cruz</td>
          <td>Buddy</td>
          <td>Vaccination</td>
          <td>₱800</td>
          <td>September 29, 2026</td>
          <td>
            <span class="status active">Paid</span>
          </td>
        </tr>
        <tr>
          <td>TXN-003</td>
          <td>Anna Reyes</td>
          <td>Coco</td>
          <td>Grooming</td>
          <td>₱600</td>
          <td>September 28, 2026</td>
          <td>
            <span class="status inactive">Pending</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</main>
@endsection
