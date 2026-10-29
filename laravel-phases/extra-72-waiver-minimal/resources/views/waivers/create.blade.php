{{-- Staff prepare a waiver for a pet, or edit one that was not signed yet ($waiver is given when editing) --}}
@php $editing = isset($waiver); @endphp
@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | ' . ($editing ? 'Edit Waiver' : 'Prepare Waiver'))
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $editing ? 'Edit Waiver ' . $waiver->reference : 'Prepare Waiver' }}</h1>
      <p>{{ $editing ? 'Change the details. This is only possible while the waiver is not signed yet.' : 'Choose the form and the pet, and fill in the details. The owner can then sign it here at the clinic or in the customer portal.' }}</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-table-card" method="post" action="{{ $editing ? route('staff.waivers.update', $waiver) : route('staff.waivers.store') }}">
    @csrf
    @if ($editing)
      @method('PUT')
    @endif
    <div class="form-group">
      <label for="waiver_template_id">Form</label>
      <select id="waiver_template_id" name="waiver_template_id" required>
        <option value="">Select form</option>
        @foreach ($templates as $template)
          <option value="{{ $template->id }}" data-procedure="{{ $template->needsProcedure() ? 1 : 0 }}" @selected((int) old('waiver_template_id', $editing ? $waiver->waiver_template_id : null) === $template->id)>{{ $template->title }}</option>
        @endforeach
      </select>
      @error('waiver_template_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
      <label for="pet_id">Pet (Owner)</label>
      <select id="pet_id" name="pet_id" required>
        <option value="">Select pet</option>
        @foreach ($pets as $pet)
          <option value="{{ $pet->id }}" data-color="{{ $pet->color }}" @selected((int) old('pet_id', $selectedPet) === $pet->id)>{{ $pet->name }} ({{ ucfirst($pet->species) }}) — {{ $pet->customer?->full_name }}</option>
        @endforeach
      </select>
      @error('pet_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
    </div>
    {{-- The blanks of the paper form: the owner's name, address, phone and the pet's species, breed, age and sex come from the records --}}
    <div id="procedureGroup">
      <div class="form-group">
        <label for="service_id">Service</label>
        <select id="service_id" name="service_id">
          <option value="">Select service (or type the procedure below)</option>
          @foreach ($services as $service)
            <option value="{{ $service->id }}" data-name="{{ $service->name }}" data-description="{{ $service->description }}" @selected((int) old('service_id', $editing ? ($waiver->details['service_id'] ?? null) : null) === $service->id)>{{ $service->name }}</option>
          @endforeach
        </select>
        <small id="serviceDescription" style="display: block; margin-top: 6px; color: #8a8f98;"></small>
        @error('service_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="procedure">Procedure <small style="color: #8a8f98; font-weight: normal;">(written in "authorize ... to perform ____"; the service name is used when empty)</small></label>
        <input type="text" id="procedure" name="procedure" value="{{ old('procedure', $editing ? $waiver->procedure : '') }}" maxlength="150" placeholder="e.g. Spay operation, Dental cleaning, Confinement">
        @error('procedure') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0 16px;">
      <div class="form-group">
        <label for="color">Color</label>
        <input type="text" id="color" name="color" value="{{ old('color', $editing ? ($waiver->details['color'] ?? '') : '') }}" maxlength="60" placeholder="e.g. Brown and white">
        @error('color') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="weight">Weight (kg)</label>
        <input type="number" id="weight" name="weight" value="{{ old('weight', $editing ? ((float) ($waiver->details['weight'] ?? '') ?: '') : '') }}" step="0.01" min="0.01" max="200" placeholder="e.g. 12.5">
        @error('weight') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="temperature">Temperature (°C)</label>
        <input type="number" id="temperature" name="temperature" value="{{ old('temperature', $editing ? ((float) ($waiver->details['temperature'] ?? '') ?: '') : '') }}" step="0.1" min="30" max="45" placeholder="e.g. 38.5">
        @error('temperature') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">{{ $editing ? 'Save Changes' : 'Prepare Waiver' }}</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ $editing ? route('staff.waivers.show', $waiver) : route('staff.waivers.index') }}'">Cancel</button>
    </div>
  </form>
</main>
<script>
  // The service and procedure boxes are needed only by a form with the blank; the color is taken from the pet's record
  (function () {
    var form = document.getElementById('waiver_template_id');
    var pet = document.getElementById('pet_id');
    var group = document.getElementById('procedureGroup');
    var procedure = document.getElementById('procedure');
    var service = document.getElementById('service_id');
    var serviceDescription = document.getElementById('serviceDescription');
    var color = document.getElementById('color');
    function showProcedure() {
      var option = form.options[form.selectedIndex];
      var needed = !option || !option.value || option.dataset.procedure === '1';
      group.hidden = !needed;
    }
    // the chosen service: its description is shown, and its name goes in the blank when the blank is empty
    var selected = service.options[service.selectedIndex];
    var filledByService = selected && selected.value && selected.dataset.name === procedure.value ? procedure.value : '';
    function showService() {
      var option = service.options[service.selectedIndex];
      serviceDescription.textContent = option && option.value ? option.dataset.description : '';
      if (option && option.value && (procedure.value === '' || procedure.value === filledByService)) {
        procedure.value = filledByService = option.dataset.name;
      }
    }
    service.addEventListener('change', showService);
    showService();
    function fillColor() {
      var option = pet.options[pet.selectedIndex];
      if (option && option.value && !color.dataset.typed) color.value = option.dataset.color || '';
    }
    color.addEventListener('input', function () { color.dataset.typed = '1'; });
    form.addEventListener('change', showProcedure);
    pet.addEventListener('change', fillColor);
    showProcedure();
    if (!color.value) fillColor();
  })();
</script>
@endsection
