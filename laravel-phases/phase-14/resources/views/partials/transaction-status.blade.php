{{-- Bill status label. Use: @include('partials.transaction-status', ['status' => $transaction->status]) --}}
@php
  [$badge, $label] = ['unpaid' => ['pending', 'Unpaid'], 'partial' => ['inactive', 'Partial'], 'paid' => ['confirmed', 'Paid'], 'void' => ['cancelled', 'Void']][$status];
@endphp
@include('partials.status-badge', ['status' => $badge, 'label' => $label])
