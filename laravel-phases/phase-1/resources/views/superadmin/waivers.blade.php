@extends('layouts.superadmin')
@section('title', 'FMH Animal Clinic | Super Admin Waiver & Consent')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')
@section('content')
<main class="superadmin-container">
  <div class="superadmin-page-heading">
    <div>
      <h1>Digital Waiver & Consent Forms</h1>
      <p>Monitor submitted digital waiver and consent records.</p>
    </div>
  </div>
  <div class="superadmin-table-card">
    <table class="superadmin-table">
      <thead>
        <tr>
          <th>Form ID</th>
          <th>Customer</th>
          <th>Pet</th>
          <th>Procedure</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>WVR-001</td>
          <td>Mark Santos</td>
          <td>Max</td>
          <td>Treatment</td>
          <td>September 29, 2026</td>
          <td>
            <span class="status active">Submitted</span>
          </td>
        </tr>
        <tr>
          <td>WVR-002</td>
          <td>John Cruz</td>
          <td>Buddy</td>
          <td>Vaccination</td>
          <td>September 29, 2026</td>
          <td>
            <span class="status active">Submitted</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</main>
@endsection
