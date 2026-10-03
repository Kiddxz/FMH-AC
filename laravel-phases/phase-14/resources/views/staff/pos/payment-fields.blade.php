{{-- Payment inputs, used by the POS page and by "Collect Payment". Needs: $suggested (amount to show first) --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0 15px;">
  <div class="form-group">
    <label for="amount">Amount Paid (₱)</label>
    <input type="number" id="amount" name="amount" step="0.01" min="0" value="{{ old('amount', number_format((float) $suggested, 2, '.', '')) }}">
    @error('amount') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
  <div class="form-group">
    <label for="method">Payment Method</label>
    <select id="method" name="method">
      @foreach (['cash' => 'Cash', 'gcash' => 'GCash', 'maya' => 'Maya', 'card' => 'Card'] as $value => $label)
        <option value="{{ $value }}" @selected(old('method', 'cash') === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('method') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
  <div class="form-group" id="reference-field">
    <label for="reference_number">Reference No. (GCash / Maya / card slip)</label>
    <input type="text" id="reference_number" name="reference_number" maxlength="50" value="{{ old('reference_number') }}">
    @error('reference_number') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
  <div class="form-group" id="tendered-field">
    <label for="amount_tendered">Cash Received (₱)</label>
    <input type="number" id="amount_tendered" name="amount_tendered" step="0.01" min="0" value="{{ old('amount_tendered') }}" placeholder="Optional">
    <p style="margin-top: 5px; color: #2e8b57;">Change: <strong id="change-text">₱0.00</strong></p>
    @error('amount_tendered') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
  </div>
</div>
<script>
  // Show "Cash Received" for cash and "Reference No." for the others, and compute the change.
  // Only a help for the cashier: the server checks and saves the real amounts.
  (function () {
    const method = document.getElementById('method');
    const amount = document.getElementById('amount');
    const tendered = document.getElementById('amount_tendered');
    function refresh() {
      const cash = method.value === 'cash';
      document.getElementById('tendered-field').style.display = cash ? '' : 'none';
      document.getElementById('reference-field').style.display = cash ? 'none' : '';
      const change = (parseFloat(tendered.value) || 0) - (parseFloat(amount.value) || 0);
      document.getElementById('change-text').textContent = '₱' + Math.max(change, 0).toFixed(2);
    }
    [method, amount, tendered].forEach(el => el.addEventListener('input', refresh));
    method.addEventListener('change', refresh);
    window.posRefreshPayment = refresh;
    refresh();
  })();
</script>
