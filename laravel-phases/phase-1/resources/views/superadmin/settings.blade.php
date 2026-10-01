@extends('layouts.superadmin')

@section('title', 'FMH Animal Clinic | System Settings')
@section('body_class', 'superadmin-page')
@section('footer', '© 2026 FMH Animal Clinic | Super Admin Panel')

@section('content')
<main class="superadmin-container">

  <div class="superadmin-page-heading">

    <div>

      <h1>
        System Settings
      </h1>

      <p>
        Maintain the settings of the FMH Animal Clinic system.
      </p>

    </div>

  </div>

  <div class="superadmin-settings-card">

    <div class="form-group">

      <label for="clinicName">
        Clinic Name
      </label>

      <input
        type="text"
        id="clinicName"
        value="FMH Animal Clinic"
      >

    </div>

    <div class="form-group">

      <label for="clinicAddress">
        Clinic Address
      </label>

      <input
        type="text"
        id="clinicAddress"
        value="Las Piñas City"
      >

    </div>

    <div class="form-group">

      <label for="clinicContact">
        Contact Number
      </label>

      <input
        type="text"
        id="clinicContact"
        value="0932-314-5969"
      >

    </div>

    <div class="form-group">

      <label for="systemStatus">
        System Status
      </label>

      <select id="systemStatus">

        <option value="active">
          Active
        </option>

        <option value="maintenance">
          Maintenance
        </option>

      </select>

    </div>

    <button
      type="button"
      class="superadmin-add-btn"
      id="saveSettingsBtn"
    >
      Save Settings
    </button>

    <div
      class="superadmin-message"
      id="settingsMessage"
    ></div>

  </div>

</main>
@endsection
