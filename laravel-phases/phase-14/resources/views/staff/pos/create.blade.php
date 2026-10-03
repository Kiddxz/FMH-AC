{{-- Point of Sale (Staff = cashier). Prices and totals shown here are only a preview: the server calculates the real ones. --}}
@php
  $source = $visit ?? $appointment;
  $lines = old('items', $firstLines);
@endphp
@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | Point of Sale')
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>Point of Sale</h1>
      <p>Bill services and products, then record the payment (cash, GCash, Maya or card, paid here at the clinic).</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>

  <div class="admin-tools">
    <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.transactions.index') }}'">📋 All Transactions &amp; Balances</button>
  </div>

  @if ($billed)
    <div class="admin-table-card" style="border-left: 5px solid #e89427;">
      <h2>Already billed</h2>
      <p style="margin: 10px 0 15px;">This {{ $visit ? 'visit' : 'appointment' }} already has bill <strong>{{ $billed->receipt_number }}</strong>.</p>
      <button class="admin-add-btn" type="button" onclick="window.location.href='{{ route('staff.transactions.show', $billed) }}'">View {{ $billed->receipt_number }}</button>
    </div>
  @else
  <form method="post" action="{{ route('staff.pos.store') }}" id="pos-form">
    @csrf

    <div class="admin-table-card">
      <div class="admin-panel-header">
        <h2>Bill For</h2>
      </div>
      @if ($source)
        <input type="hidden" name="patient_visit_id" value="{{ $visit?->id }}">
        <input type="hidden" name="appointment_id" value="{{ $appointment?->id }}">
        <table class="admin-table">
          <tbody>
            <tr><th>Type</th><td>{{ $appointment ? 'Appointment ' . $appointment->reference : 'Walk-in' }}@if ($visit) · Queue #{{ $visit->queue_number }} ({{ ucfirst($visit->status) }})@endif</td></tr>
            <tr><th>Customer</th><td>{{ $source->customer?->full_name }} · {{ $source->customer?->contact_number }}</td></tr>
            <tr><th>Pet</th><td>{{ $source->pet?->icon }} {{ $source->pet?->name }}</td></tr>
          </tbody>
        </table>
      @else
        <p style="color: #666; margin-bottom: 12px;">Counter sale (e.g. dog food only). Choosing the customer is optional.</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0 15px;">
          <div class="form-group">
            <label for="customer_id">Customer</label>
            <select id="customer_id" name="customer_id">
              <option value="">Walk-in buyer (no record)</option>
              @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((int) old('customer_id', $selectedCustomer) === $customer->id)>{{ $customer->full_name }} · {{ $customer->contact_number }}</option>
              @endforeach
            </select>
            @error('customer_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
          </div>
          <div class="form-group">
            <label for="pet_id">Pet (optional)</label>
            <select id="pet_id" name="pet_id">
              <option value="">No pet</option>
              @foreach ($pets as $pet)
                <option value="{{ $pet->id }}" data-customer="{{ $pet->customer_id }}" @selected((int) old('pet_id') === $pet->id)>{{ $pet->name }} ({{ ucfirst($pet->species) }})</option>
              @endforeach
            </select>
            @error('pet_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
          </div>
        </div>
      @endif
    </div>

    <div class="admin-table-card">
      <div class="admin-panel-header">
        <h2>Services &amp; Products</h2>
        <button class="action-view" type="button" id="add-line">+ Add Line</button>
      </div>
      @error('items') <p style="color: #c0392b; margin-bottom: 10px;">{{ $message }}</p> @enderror
      @error('quantity') <p style="color: #c0392b; margin-bottom: 10px;">{{ $message }}</p> @enderror
      <table class="admin-table" id="pos-lines">
        <thead>
          <tr><th>Service / Product</th><th style="width: 110px;">Qty</th><th>Price</th><th>Amount</th><th></th></tr>
        </thead>
        <tbody>
          @foreach ($lines as $i => $line)
            @include('staff.pos.line', ['index' => $i, 'line' => $line])
          @endforeach
        </tbody>
      </table>
      <template id="line-template">
        @include('staff.pos.line', ['index' => '__INDEX__', 'line' => ['item' => '', 'quantity' => 1]])
      </template>

      <div style="display: flex; justify-content: flex-end; margin-top: 15px;">
        <table style="min-width: 300px;">
          <tr><td style="padding: 6px 12px;">Subtotal</td><td style="text-align: right;"><strong id="subtotal-text">₱0.00</strong></td></tr>
          <tr>
            <td style="padding: 6px 12px;"><label for="discount">Discount (₱)</label></td>
            <td><input type="number" id="discount" name="discount" step="0.01" min="0" value="{{ old('discount', '0') }}" style="width: 130px; text-align: right; padding: 9px 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px;"></td>
          </tr>
          <tr><td style="padding: 6px 12px; font-size: 18px;">Total</td><td style="text-align: right; font-size: 18px;"><strong id="total-text">₱0.00</strong></td></tr>
        </table>
      </div>
      @error('discount') <p style="color: #c0392b; margin-top: 5px; text-align: right;">{{ $message }}</p> @enderror
    </div>

    <div class="admin-table-card">
      <div class="admin-panel-header">
        <div>
          <h2>Payment</h2>
          <p>Enter 0 to save the bill without payment. A partial payment leaves a balance that can be collected later.</p>
        </div>
      </div>
      @include('staff.pos.payment-fields', ['suggested' => 0])
    </div>

    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">💾 Save Bill</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ route('staff.transactions.index') }}'">Cancel</button>
    </div>
  </form>
  @endif
</main>
@endsection

@push('scripts')
<script>
  // POS helper: add/remove lines, show prices and totals, filter pets by customer.
  (function () {
    const form = document.getElementById('pos-form');
    if (!form) return;
    const body = document.querySelector('#pos-lines tbody');
    const peso = n => '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const amount = document.getElementById('amount');
    let next = body.rows.length;
    let amountTyped = {{ old('amount') !== null ? 'true' : 'false' }};   // after an error, keep what the cashier typed

    function totals() {
      let subtotal = 0;
      body.querySelectorAll('tr').forEach(row => {
        const option = row.querySelector('select').selectedOptions[0];
        const price = parseFloat(option?.dataset.price || 0);
        const qty = parseInt(row.querySelector('input[type=number]').value || 0, 10);
        row.querySelector('.line-price').textContent = price ? peso(price) : '—';
        row.querySelector('.line-amount').textContent = peso(price * qty);
        subtotal += price * qty;
      });
      const discount = parseFloat(document.getElementById('discount').value || 0);
      const total = Math.max(subtotal - discount, 0);
      document.getElementById('subtotal-text').textContent = peso(subtotal);
      document.getElementById('total-text').textContent = peso(total);
      if (!amountTyped) amount.value = total.toFixed(2);   // pay in full unless the cashier typed another amount
      window.posRefreshPayment && window.posRefreshPayment();
    }

    document.getElementById('add-line').addEventListener('click', () => {
      const html = document.getElementById('line-template').innerHTML.replaceAll('__INDEX__', next++);
      body.insertAdjacentHTML('beforeend', html);
      totals();
    });
    body.addEventListener('click', e => {
      if (e.target.classList.contains('remove-line') && body.rows.length > 1) {
        e.target.closest('tr').remove();
        totals();
      }
    });
    form.addEventListener('input', e => {
      if (e.target === amount) amountTyped = true;
      totals();
    });
    form.addEventListener('change', totals);

    const customer = document.getElementById('customer_id');
    const pet = document.getElementById('pet_id');
    function filterPets() {
      pet.querySelectorAll('option[data-customer]').forEach(o => {
        o.hidden = o.dataset.customer !== customer.value;
        if (o.hidden && o.selected) pet.value = '';
      });
    }
    if (customer && pet) {
      customer.addEventListener('change', filterPets);
      filterPets();
    }

    totals();
  })();
</script>
@endpush
