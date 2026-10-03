{{-- New pet fields for the walk-in forms --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0 15px;">
  <div class="form-group">
    <label for="pet_name">Pet Name</label>
    <input type="text" id="pet_name" name="pet_name" value="{{ old('pet_name') }}" placeholder="e.g. Choco">
    @error('pet_name') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
  <div class="form-group">
    <label for="species">Species</label>
    <select id="species" name="species">
      <option value="">Select species</option>
      @foreach (\App\Models\Pet::SPECIES as $option)
        <option value="{{ $option }}" @selected(old('species') === $option)>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
    @error('species') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
  <div class="form-group">
    <label for="breed">Breed (optional)</label>
    <input type="text" id="breed" name="breed" value="{{ old('breed') }}" placeholder="e.g. Aspin">
    @error('breed') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
  <div class="form-group">
    <label for="gender">Sex</label>
    <select id="gender" name="gender">
      <option value="">Select sex</option>
      <option value="male" @selected(old('gender') === 'male')>Male</option>
      <option value="female" @selected(old('gender') === 'female')>Female</option>
    </select>
    @error('gender') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
  <div class="form-group">
    <label for="age">Age (years)</label>
    <input type="number" id="age" name="age" value="{{ old('age') }}" min="0" max="50" placeholder="e.g. 2">
    @error('age') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
</div>
