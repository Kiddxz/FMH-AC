{{-- Printable receipt (capstone SCOPE-08, Fig 6.5). The browser's print window can also "Save as PDF". --}}
@php
  $methods = ['cash' => 'Cash', 'gcash' => 'GCash', 'maya' => 'Maya', 'card' => 'Card'];
  $change = $transaction->payments->sum('change_given');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $transaction->receipt_number }} | FMH Animal Clinic</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; max-width: 380px; margin: 25px auto; padding: 0 15px; font-size: 14px; line-height: 1.5; }
        .center { text-align: center; }
        .muted { color: #666; font-size: 12px; }
        h1 { margin: 0; font-size: 20px; }
        hr { border: none; border-top: 1px dashed #999; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        td.num { text-align: right; white-space: nowrap; }
        .total td { font-size: 16px; font-weight: bold; }
        .void { border: 3px solid #d9534f; color: #d9534f; font-size: 26px; font-weight: bold; text-align: center; padding: 6px; margin: 10px 0; letter-spacing: 6px; }
        .actions { margin: 15px 0; text-align: center; }
        .actions button { padding: 10px 18px; background: #e89427; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        @media print { .actions { display: none; } body { margin: 0 auto; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">🖨️ Print / Save as PDF</button></div>

    <div class="center">
        <h1>🐾 FMH Animal Clinic</h1>
        <div class="muted">Las Piñas City</div>
        <div style="margin-top: 8px;"><strong>RECEIPT</strong></div>
        <div><strong>{{ $transaction->receipt_number }}</strong></div>
        <div class="muted">{{ $transaction->created_at->format('F j, Y g:i A') }}</div>
    </div>

    @if ($transaction->status === 'void')
        <div class="void">VOID</div>
        <div class="muted center">Reason: {{ $transaction->void_reason }}</div>
    @endif

    <hr>
    <table>
        <tr><td>Customer</td><td class="num">{{ $transaction->customer?->full_name ?? 'Walk-in buyer' }}</td></tr>
        @if ($transaction->pet)
            <tr><td>Pet</td><td class="num">{{ $transaction->pet->name }}</td></tr>
        @endif
        <tr><td>Type</td><td class="num">{{ \App\Models\Transaction::TYPES[$transaction->transaction_type] }}</td></tr>
        <tr><td>Cashier</td><td class="num">{{ $transaction->cashier?->full_name ?? '—' }}</td></tr>
    </table>
    <hr>

    <table>
        @foreach ($transaction->items as $item)
            <tr>
                <td>{{ $item->description }}<br><span class="muted">{{ $item->quantity }} × ₱{{ number_format($item->unit_price, 2) }}</span></td>
                <td class="num">₱{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <hr>

    <table>
        <tr><td>Subtotal</td><td class="num">₱{{ number_format($transaction->subtotal, 2) }}</td></tr>
        @if ($transaction->discount > 0)
            <tr><td>Discount</td><td class="num">− ₱{{ number_format($transaction->discount, 2) }}</td></tr>
        @endif
        <tr class="total"><td>TOTAL</td><td class="num">₱{{ number_format($transaction->total, 2) }}</td></tr>
    </table>
    <hr>

    <table>
        @forelse ($transaction->payments as $payment)
            <tr>
                <td>{{ $methods[$payment->method] }}@if ($payment->reference_number) <span class="muted">Ref {{ $payment->reference_number }}</span>@endif<br><span class="muted">{{ $payment->paid_at->format('M j, Y g:i A') }}</span></td>
                <td class="num">₱{{ number_format($payment->amount, 2) }}</td>
            </tr>
            @if ($payment->method === 'cash' && $payment->change_given > 0)
                <tr><td class="muted">&nbsp;&nbsp;Cash received ₱{{ number_format($payment->amount_tendered, 2) }}</td><td></td></tr>
            @endif
        @empty
            <tr><td class="muted">No payment yet</td><td></td></tr>
        @endforelse
        <tr><td><strong>Amount Paid</strong></td><td class="num"><strong>₱{{ number_format($transaction->amount_paid, 2) }}</strong></td></tr>
        <tr><td>Change</td><td class="num">₱{{ number_format($change, 2) }}</td></tr>
        <tr class="total"><td>BALANCE</td><td class="num">₱{{ number_format($transaction->balance, 2) }}</td></tr>
    </table>
    <hr>

    <p class="center muted">Thank you for trusting FMH Animal Clinic!<br>Printed {{ now()->format('F j, Y g:i A') }}</p>
</body>
</html>
