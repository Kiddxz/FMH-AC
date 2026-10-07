{{-- Date + time-slot fields. The time list is loaded from /appointments/slots when a date is picked.
     Use: @include('partials.appointments.slot-picker', ['date' => ..., 'time' => ..., 'ignore' => $appointment->id, 'group' => 'form-group']) --}}
@php
  $group = $group ?? 'form-group';
  $ignore = $ignore ?? null;
@endphp
<div class="{{ $group }}">
  <label for="appointment_date">Appointment Date</label>
  <input type="date" id="appointment_date" name="appointment_date" value="{{ $date }}"
         min="{{ today()->toDateString() }}" max="{{ today()->addMonths(3)->toDateString() }}" required>
</div>
<div class="{{ $group }}">
  <label for="appointment_time">Appointment Time</label>
  <select id="appointment_time" name="appointment_time" required>
    <option value="">Choose a date first</option>
  </select>
  <small id="slotHelp" style="display: block; margin-top: 6px; color: #777;"></small>
</div>
<script>
  (function () {
    const dateInput = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const help = document.getElementById('slotHelp');
    const ignore = @json($ignore);
    let selected = @json($time ? substr($time, 0, 5) : '');
    let lastRequest = 0;   // only the newest answer is shown, if the date is changed quickly

    async function loadSlots() {
      const requestNumber = ++lastRequest;
      timeSelect.innerHTML = '';
      if (!dateInput.value) {
        timeSelect.add(new Option('Choose a date first', ''));
        help.textContent = '';
        return;
      }
      timeSelect.add(new Option('Loading time slots...', ''));
      let url = @json($slotsUrl ?? route('appointments.slots')) + '?date=' + encodeURIComponent(dateInput.value);
      if (ignore) url += '&ignore=' + ignore;

      try {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (requestNumber !== lastRequest) return;
        timeSelect.innerHTML = '';

        if (!data.open) {
          timeSelect.add(new Option('The clinic is closed on this day', ''));
          help.textContent = 'Please choose another date.';
          return;
        }

        timeSelect.add(new Option('Select a time', ''));
        let free = 0;
        data.slots.forEach(function (slot) {
          let label = slot.label;
          if (slot.available) {
            label += ' (' + slot.remaining + ' left)';
            free++;
          } else {
            label += slot.remaining > 0 ? ' (time has passed)' : ' (full)';
          }
          const option = new Option(label, slot.time);
          option.disabled = !slot.available;
          if (slot.time === selected && slot.available) option.selected = true;
          timeSelect.add(option);
        });
        help.textContent = free > 0 ? free + ' time slots are available on this day.' : 'No free time slots on this day. Please choose another date.';
      } catch (error) {
        if (requestNumber !== lastRequest) return;
        timeSelect.innerHTML = '';
        timeSelect.add(new Option('Could not load the time slots. Please refresh the page.', ''));
      }
    }

    dateInput.addEventListener('change', function () {
      selected = '';
      loadSlots();
    });
    // keep the chosen time if the list is loaded again
    timeSelect.addEventListener('change', function () {
      selected = timeSelect.value;
    });
    loadSlots();
  })();
</script>
