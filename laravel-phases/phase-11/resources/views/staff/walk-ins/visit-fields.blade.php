{{-- Service, veterinarian and notes for the walk-in forms ($services, $vets) --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0 15px;">
  <div class="form-group">
    <label for="service_id">Service</label>
    <select id="service_id" name="service_id" required>
      <option value="">Select service</option>
      @foreach ($services as $service)
        <option value="{{ $service->id }}" @selected((int) old('service_id') === $service->id)>{{ $service->name }} — ₱{{ number_format($service->price, 2) }}</option>
      @endforeach
    </select>
    @error('service_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
  <div class="form-group">
    <label for="veterinarian_id">Veterinarian (optional)</label>
    <select id="veterinarian_id" name="veterinarian_id">
      <option value="">Not assigned yet</option>
      @foreach ($vets as $vet)
        <option value="{{ $vet->id }}" @selected((int) old('veterinarian_id') === $vet->id)>Dr. {{ $vet->full_name }}</option>
      @endforeach
    </select>
    @error('veterinarian_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
</div>
<div class="form-group">
  <label for="notes">Reason for Visit / Notes (optional)</label>
  <textarea id="notes" name="notes" rows="2" placeholder="e.g. Not eating since yesterday">{{ old('notes') }}</textarea>
  @error('notes') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
</div>
