@extends('guest.layout')
@section('title', 'Your Booking ' . $appointment->reference)
@section('content')
  <h1>Booking {{ $appointment->reference }}</h1>
  <p class="account-description">{{ $appointment->customer->first_name }} {{ mb_substr($appointment->customer->last_name, 0, 1) }}. · {{ $appointment->pet->name }}</p>
  @include('partials.alerts')

  <div class="guest-summary">
    <div><span>Status</span><span>@include('partials.status-badge', ['status' => $appointment->status])</span></div>
    <div><span>Services</span><span>{{ $appointment->service_names }}</span></div>
    <div><span>Date</span><span>{{ $appointment->appointment_date->format('l, F j, Y') }}</span></div>
    <div><span>Time</span><span>{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</span></div>
    <div><span>Veterinarian</span><span>{{ $appointment->veterinarian ? 'Dr. ' . $appointment->veterinarian->full_name : 'To be assigned by the clinic' }}</span></div>
    <div><span>Estimated total</span><span>₱{{ number_format($appointment->total_price, 2) }}{{ $bill ? '' : ' (pay at the clinic)' }}</span></div>
    @if ($appointment->status === 'cancelled' && $appointment->cancel_reason)
      <div><span>Cancel reason</span><span>{{ $appointment->cancel_reason }}</span></div>
    @endif
  </div>

  @if ($bill)
    <div class="guest-pay">
      @if ($bill->status === 'paid')
        <strong style="color: #287a43;">Paid online: ₱{{ number_format($bill->amount_paid, 2) }}</strong> ({{ $bill->receipt_number }})
      @elseif (in_array($bill->status, ['unpaid', 'partial'], true) && $appointment->status !== 'cancelled')
        <strong>Online payment: ₱{{ number_format($bill->balance, 2) }} to pay</strong><br>
        <a href="{{ route('pay.show', $bill->pay_token) }}">Pay Online Now</a>
      @endif
    </div>
  @endif

  @if ($canCancel)
    <form action="{{ route('guest.lookup.cancel') }}" method="post" onsubmit="return confirm('Cancel booking {{ $appointment->reference }}?');">
      @csrf
      <div class="form-group">
        <label for="cancel_reason">Need to cancel? Tell the clinic why</label>
        <textarea id="cancel_reason" name="cancel_reason" rows="2" required maxlength="500" placeholder="e.g. I have a schedule conflict">{{ old('cancel_reason') }}</textarea>
      </div>
      <button class="regisbtn" type="submit" style="background: #d9534f;">Cancel Booking</button>
    </form>
  @endif

  <div class="guest-links">
    <a href="{{ route('guest.book') }}">Make a new booking</a> · <a href="{{ route('guest.lookup') }}">Check another booking</a> · <a href="{{ route('home') }}">Home</a>
  </div>
@endsection
