{{-- One bill: its lines, payments, "Collect Payment" (Staff), "Void" (Vet/Admin) and the receipt --}}
@php
  $p = $area === 'superadmin' ? 'superadmin' : 'admin';
  $indexRoute = $area === 'superadmin' ? 'superadmin.transactions' : $area . '.transactions.index';
  $methods = \App\Models\Payment::LABELS;
  $canPay = $area === 'staff' && auth()->user()->can('pay', $transaction);
  $canVoid = $area === 'admin' && auth()->user()->can('void', $transaction);
  // Online payment (PayMongo): only the cashier, only when the key is in .env
  $online = $area === 'staff' && \App\Services\PayMongo::enabled() && auth()->user()->can('pos.manage') && $transaction->status !== 'void';
@endphp
@extends('layouts.' . $area)
@section('title', 'FMH Animal Clinic | ' . $transaction->receipt_number)
@section('body_class', $area === 'superadmin' ? 'superadmin-page' : 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | ' . ['staff' => 'Staff', 'admin' => 'Admin', 'superadmin' => 'Super Admin'][$area] . ' Panel')
@section('content')
<main class="{{ $p }}-container">
  <div class="{{ $p }}-page-heading">
    <div>
      <h1>{{ $transaction->receipt_number }} · ₱{{ number_format($transaction->total, 2) }}</h1>
      <p>{{ $transaction->customer?->full_name ?? 'Walk-in buyer' }}@if ($transaction->pet) · {{ $transaction->pet->name }}@endif · @include('partials.transaction-status', ['status' => $transaction->status])</p>
    </div>
  </div>

  @if ($transaction->status === 'void')
    <div class="{{ $p }}-table-card" style="padding: 18px 25px; border-left: 5px solid #d9534f;">
      <strong style="color: #d9534f;">VOID</strong> · {{ $transaction->voided_at?->format('F j, Y g:i A') }}
      · by {{ $transaction->voider ? 'Dr. ' . $transaction->voider->full_name : '—' }}
      <p style="margin-top: 6px; white-space: pre-line;">Reason: {{ $transaction->void_reason }}</p>
    </div>
  @endif

  <div class="{{ $p }}-table-card" style="padding: 25px;">
    <h2 style="margin-bottom: 12px;">Bill Details</h2>
    <table class="{{ $p }}-table">
      <tbody>
        <tr><th>Receipt No.</th><td>{{ $transaction->receipt_number }}</td></tr>
        <tr><th>Date</th><td>{{ $transaction->created_at->format('F j, Y g:i A') }}</td></tr>
        <tr><th>Type</th><td>
          {{ \App\Models\Transaction::TYPES[$transaction->transaction_type] }}
          @if ($transaction->appointment) · Appointment {{ $transaction->appointment->reference }} @endif
          @if ($transaction->visit) · Queue #{{ $transaction->visit->queue_number }} on {{ $transaction->visit->visit_date->format('M j, Y') }} @endif
        </td></tr>
        <tr><th>Customer</th><td>{{ $transaction->customer ? $transaction->customer->full_name . ' · ' . $transaction->customer->contact_number : 'Walk-in buyer' }}</td></tr>
        <tr><th>Pet</th><td>{{ $transaction->pet?->name ?? '—' }}</td></tr>
        <tr><th>Cashier</th><td>{{ $transaction->cashier?->full_name ?? '—' }}</td></tr>
      </tbody>
    </table>
  </div>

  <div class="{{ $p }}-table-card" style="padding: 25px;">
    <h2 style="margin-bottom: 12px;">Items</h2>
    <table class="{{ $p }}-table">
      <thead>
        <tr><th>Item</th><th>Type</th><th>Qty</th><th>Price</th><th>Amount</th></tr>
      </thead>
      <tbody>
        @foreach ($transaction->items as $item)
          <tr>
            <td>{{ $item->description }}</td>
            <td>{{ ucfirst($item->item_type) }}</td>
            <td>{{ $item->quantity }}</td>
            <td>₱{{ number_format($item->unit_price, 2) }}</td>
            <td>₱{{ number_format($item->line_total, 2) }}</td>
          </tr>
        @endforeach
        <tr><th colspan="4" style="text-align: right;">Subtotal</th><td>₱{{ number_format($transaction->subtotal, 2) }}</td></tr>
        @if ($transaction->discount > 0)
          <tr><th colspan="4" style="text-align: right;">Discount</th><td>− ₱{{ number_format($transaction->discount, 2) }}</td></tr>
        @endif
        <tr><th colspan="4" style="text-align: right;">Total</th><td><strong>₱{{ number_format($transaction->total, 2) }}</strong></td></tr>
        <tr><th colspan="4" style="text-align: right;">Amount Paid</th><td>₱{{ number_format($transaction->amount_paid, 2) }}</td></tr>
        <tr><th colspan="4" style="text-align: right;">Balance</th><td><strong style="color: {{ $transaction->balance > 0 ? '#d9534f' : '#2e8b57' }};">₱{{ number_format($transaction->balance, 2) }}</strong></td></tr>
      </tbody>
    </table>
  </div>

  <div class="{{ $p }}-table-card" style="padding: 25px;">
    <h2 style="margin-bottom: 12px;">Payments</h2>
    <table class="{{ $p }}-table">
      <thead>
        <tr><th>Date</th><th>Method</th><th>Reference No.</th><th>Amount</th><th>Cash Received</th><th>Change</th><th>Received By</th></tr>
      </thead>
      <tbody>
        @forelse ($transaction->payments as $payment)
          <tr>
            <td>{{ $payment->paid_at->format('M j, Y g:i A') }}</td>
            <td>{{ $methods[$payment->method] }}</td>
            <td>{{ $payment->reference_number ?: '—' }}</td>
            <td>₱{{ number_format($payment->amount, 2) }}</td>
            <td>{{ $payment->method === 'cash' ? '₱' . number_format($payment->amount_tendered, 2) : '—' }}</td>
            <td>{{ $payment->method === 'cash' ? '₱' . number_format($payment->change_given, 2) : '—' }}</td>
            <td>{{ $payment->receiver?->full_name ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align: center; color: #777;">No payment yet.</td></tr>
        @endforelse
      </tbody>
    </table>

    @if ($canPay)
      <form method="post" action="{{ route('staff.transactions.pay', $transaction) }}" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 18px;">
        @csrf
        <h3 style="margin-bottom: 8px;">💵 Collect Payment</h3>
        <p style="color: #666; margin-bottom: 12px;">Balance: <strong>₱{{ number_format($transaction->balance, 2) }}</strong>. The customer may pay all of it or only a part.</p>
        @include('staff.pos.payment-fields', ['suggested' => $transaction->balance])
        <button class="admin-add-btn" type="submit">Save Payment</button>
      </form>
    @endif
  </div>

  @if ($online && ($canPay || $transaction->paymongo_checkout_id))
    <div class="{{ $p }}-table-card" style="padding: 25px;">
      <h3 style="margin-bottom: 8px;">📱 Pay Online (PayMongo)</h3>
      @error('paymongo') <p style="color: #c0392b; margin-bottom: 10px;">{{ $message }}</p> @enderror
      @if ($transaction->pay_token)
        @php $payUrl = route('pay.show', $transaction->pay_token); @endphp
        <p style="color: #666; margin-bottom: 14px;">The customer scans this QR code (or opens the link) to see this bill and pay with GCash, Maya or card. No account needed.</p>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">
          <div id="pay-qr" data-url="{{ $payUrl }}" style="width: 198px; height: 198px; box-sizing: border-box; padding: 8px; background: #fff; border: 1px solid #eee6db; border-radius: 12px;"></div>
          <div style="flex: 1; min-width: 220px;">
            <input type="text" id="pay-url" value="{{ $payUrl }}" readonly aria-label="Pay link" style="width: 100%; padding: 9px 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; margin-bottom: 10px;">
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
              <button class="action-view" type="button" id="copy-pay-url">📋 Copy Link</button>
              <button class="action-view" type="button" onclick="window.open('{{ $payUrl }}', '_blank')">Open Customer Page</button>
              @if ($transaction->paymongo_checkout_id)
                <form method="post" action="{{ route('staff.transactions.pay-check', $transaction) }}">
                  @csrf
                  <button class="action-edit" type="submit">Check Online Payment</button>
                </form>
              @endif
            </div>
          </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
          // QR code of the pay link (if the QR library cannot load, the link still works)
          (function () {
            var box = document.getElementById('pay-qr');
            if (window.QRCode) {
              new QRCode(box, { text: box.dataset.url, width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M });
            } else {
              box.style.display = 'none';
            }
            document.getElementById('copy-pay-url').addEventListener('click', function () {
              var field = document.getElementById('pay-url');
              field.select();
              (navigator.clipboard ? navigator.clipboard.writeText(field.value) : Promise.resolve(document.execCommand('copy')))
                .then(() => { this.textContent = 'Copied!'; });
            });
          })();
        </script>
      @else
        <p style="color: #666; margin-bottom: 12px;">For customers who want to pay with GCash, Maya or card on their own phone. This makes a link and a QR code that shows this bill (no account needed).</p>
        <form method="post" action="{{ route('staff.transactions.pay-link', $transaction) }}">
          @csrf
          <button class="admin-add-btn" type="submit">Make Pay Link</button>
        </form>
      @endif
    </div>
  @endif

  @if ($canVoid)
    <div class="{{ $p }}-table-card" style="padding: 25px;">
      <form method="post" action="{{ route('admin.transactions.void', $transaction) }}" onsubmit="return confirm('Void {{ $transaction->receipt_number }}? This cannot be undone.');">
        @csrf
        @method('PATCH')
        <h3 style="margin-bottom: 8px;">🚫 Void This Bill</h3>
        <p style="color: #666; margin-bottom: 12px;">Use this only for a mistake or a refund. The bill stays in the records as VOID and its products go back to stock.</p>
        <div class="form-group">
          <label for="void_reason">Reason</label>
          <textarea id="void_reason" name="void_reason" rows="2" required minlength="5" maxlength="500" placeholder="e.g. Wrong service was charged">{{ old('void_reason') }}</textarea>
          @error('void_reason') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
        </div>
        <button class="action-delete" type="submit">Void Bill</button>
      </form>
    </div>
  @endif

  <div class="{{ $p }}-tools">
    <button class="action-view" type="button" onclick="window.location.href='{{ route($indexRoute) }}'">← Back to {{ $area === 'admin' ? 'Payments' : 'Transactions' }}</button>
    <button class="action-view" type="button" onclick="window.open('{{ route($area . '.transactions.receipt', $transaction) }}', '_blank')">🧾 Print Receipt</button>
  </div>
</main>
@endsection
