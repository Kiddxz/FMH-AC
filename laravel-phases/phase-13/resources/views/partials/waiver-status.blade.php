{{-- Waiver status label. Use: @include('partials.waiver-status', ['status' => $waiver->status]) --}}
@php
  [$badge, $label] = ['pending' => ['pending', 'Waiting for signature'], 'signed' => ['confirmed', 'Signed'], 'reviewed' => ['completed', 'Reviewed']][$status];
@endphp
@include('partials.status-badge', ['status' => $badge, 'label' => $label])
