{{-- Choose ONE OR MORE services for an appointment (customer booking and the clinic's form).
     Needs: $services, $selected (array of service ids already chosen)
     Sends: service_ids[] --}}
@php
  $selected = array_map('intval', (array) $selected);
  $max = \App\Models\Appointment::MAX_SERVICES;
@endphp
<style>
  .service-picker { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 10px; }
  .service-option { position: relative; display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border: 1px solid #e6ded3; border-radius: 12px; background: #fff; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
  .service-option:hover { border-color: #e89427; }
  .service-option input { width: 18px; height: 18px; margin: 2px 0 0; accent-color: #e89427; flex-shrink: 0; }
  .service-option.checked { border-color: #e89427; background: #fff7ec; }
  .service-option strong { display: block; color: #26364a; font-size: 15px; }
  .service-option small { display: block; margin-top: 3px; color: #777; font-size: 13px; }
  .service-total { display: flex; justify-content: space-between; gap: 10px; margin-top: 12px; padding: 10px 14px; border-radius: 10px; background: #f8f4ee; color: #26364a; font-size: 14px; }
  .service-total strong { color: #e89427; font-size: 16px; }
</style>
<div class="service-picker" id="service-picker" data-max="{{ $max }}">
  @foreach ($services as $service)
    @php $isChecked = in_array($service->id, $selected, true); @endphp
    <label class="service-option {{ $isChecked ? 'checked' : '' }}">
      <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" data-price="{{ $service->price }}" data-minutes="{{ $service->duration_minutes }}" @checked($isChecked)>
      <span>
        <strong>{{ $service->name }}</strong>
        <small>₱{{ number_format($service->price, 2) }} · about {{ $service->duration_minutes }} min</small>
      </span>
    </label>
  @endforeach
</div>
<div class="service-total" id="service-total">
  <span id="service-count">No service chosen yet</span>
  <span>Estimated total: <strong id="service-sum">₱0.00</strong></span>
</div>
<small style="display: block; margin-top: 6px; color: #777;">Choose one or more services (up to {{ $max }}). Final price is confirmed by the clinic.</small>
@error('service_ids') <p style="color: #c0392b; margin-top: 6px;">{{ $message }}</p> @enderror
@error('service_ids.*') <p style="color: #c0392b; margin-top: 6px;">{{ $message }}</p> @enderror
<script>
  // Live total of the chosen services, and at least one / at most MAX can be chosen.
  // Only a help: the server checks the services and their prices again.
  (function () {
    var picker = document.getElementById('service-picker');
    var boxes = picker.querySelectorAll('input[type="checkbox"]');
    var max = parseInt(picker.dataset.max, 10);
    var peso = function (n) { return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

    function refresh() {
      var count = 0, sum = 0, minutes = 0;
      boxes.forEach(function (box) {
        box.closest('.service-option').classList.toggle('checked', box.checked);
        if (box.checked) {
          count++;
          sum += parseFloat(box.dataset.price) || 0;
          minutes += parseInt(box.dataset.minutes, 10) || 0;
        }
      });
      boxes.forEach(function (box) { box.disabled = !box.checked && count >= max; });
      document.getElementById('service-count').textContent = count === 0
        ? 'No service chosen yet'
        : count + (count === 1 ? ' service' : ' services') + ' · about ' + minutes + ' min';
      document.getElementById('service-sum').textContent = peso(sum);
      // the browser will not send the form until at least one service is checked
      boxes[0].setCustomValidity(count === 0 ? 'Please choose at least one service.' : '');
    }

    boxes.forEach(function (box) { box.addEventListener('change', refresh); });
    refresh();
  })();
</script>
