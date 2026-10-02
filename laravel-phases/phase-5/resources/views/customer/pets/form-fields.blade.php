{{-- Pet form fields shared by "Add New Pet" and "Edit Pet".
     $pet is an empty Pet when adding, or the saved pet when editing. --}}
<div class="form-group">
  <label for="petname">Pet Name</label>
  <input type="text" id="petname" name="petname" value="{{ old('petname', $pet->name) }}" placeholder="Enter your pet's name" required>
</div>
<div class="form-group">
  <label for="species">Species</label>
  <select id="species" name="species" required>
    <option value="">Select species</option>
    @foreach (\App\Models\Pet::SPECIES as $species)
      <option value="{{ $species }}" @selected(old('species', $pet->species) === $species)>{{ ucfirst($species) }}</option>
    @endforeach
  </select>
</div>
<div class="form-group">
  <label for="breed">Breed</label>
  <input type="text" id="breed" name="breed" value="{{ old('breed', $pet->breed) }}" placeholder="Enter your pet's breed" required>
</div>
<div class="form-group">
  <label for="sex">Sex</label>
  <select id="sex" name="sex" required>
    <option value="">Select sex</option>
    <option value="male" @selected(old('sex', $pet->gender) === 'male')>Male</option>
    <option value="female" @selected(old('sex', $pet->gender) === 'female')>Female</option>
  </select>
</div>
<div class="form-group">
  <label for="age">Age (years)</label>
  <input type="number" id="age" name="age" value="{{ old('age', $pet->age_years) }}" placeholder="Enter your pet's age (0 if below 1 year)" min="0" max="50" required>
</div>
<div class="form-group">
  <label for="color">Color / Markings</label>
  <input type="text" id="color" name="color" value="{{ old('color', $pet->color) }}" placeholder="e.g. Brown with white spots (optional)">
</div>
<div class="form-group">
  <label for="notes">Additional Information</label>
  <textarea id="notes" name="notes" rows="4" placeholder="Enter any additional information about your pet">{{ old('notes', $pet->notes) }}</textarea>
</div>
