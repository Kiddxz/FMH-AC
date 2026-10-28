@extends('layouts.customer')
@section('title', 'FMH Animal Clinic | ' . $pet->name)
@section('body_class', 'dashboard-page')
@section('content')
<main class="form-page">
  <div class="page-heading pet-heading">
    <div>
      <h1>{{ $pet->icon }} {{ $pet->name }}</h1>
      <p>Pet profile, vaccinations and care instructions from the clinic.</p>
    </div>
    <a href="{{ route('portal.pets.edit', $pet) }}" class="primary-btn">Edit Pet</a>
  </div>

  <section class="pet-form" style="margin-bottom: 25px;">
    <h2>Pet Information</h2>
    <div class="history-details" style="border-top: none; padding-top: 0;">
      <p><strong>Species:</strong> {{ ucfirst($pet->species) }}</p>
      <p><strong>Breed:</strong> {{ $pet->breed ?: '—' }}</p>
      <p><strong>Sex:</strong> {{ ucfirst($pet->gender) }}</p>
      <p><strong>Age:</strong> {{ $pet->age_text }}</p>
      <p><strong>Color / Markings:</strong> {{ $pet->color ?: '—' }}</p>
      <p><strong>Additional Information:</strong> {{ $pet->notes ?: '—' }}</p>
    </div>
  </section>

  <section class="pet-form" style="margin-bottom: 25px;">
    <h2>Vaccination History</h2>
    @forelse ($pet->vaccinations as $vaccination)
      <div class="history-details" @if ($loop->first) style="border-top: none; padding-top: 0;" @endif>
        <p><strong>{{ $vaccination->vaccine_name }}</strong></p>
        <p>Given: {{ $vaccination->date_given->format('F j, Y') }}</p>
        <p>Next due: {{ $vaccination->next_due_date ? $vaccination->next_due_date->format('F j, Y') : '—' }}</p>
      </div>
    @empty
      <p style="color: #666;">No vaccinations recorded yet.</p>
    @endforelse
  </section>

  <section class="pet-form" style="margin-bottom: 25px;">
    <h2>Care Instructions</h2>
    @forelse ($pet->careInstructions as $care)
      <div class="history-details" @if ($loop->first) style="border-top: none; padding-top: 0;" @endif>
        <p><strong>{{ $care->title }}</strong> ({{ $care->released_at->format('F j, Y') }})</p>
        <p style="white-space: pre-line;">{{ $care->instructions }}</p>
        @if ($care->medicalRecord?->prescriptions->isNotEmpty())
          <p><strong>Medicines to give:</strong></p>
          @foreach ($care->medicalRecord->prescriptions as $p)
            <p>• {{ $p->medicine_name }}: {{ $p->dosage }}, {{ $p->frequency }}@if ($p->duration), for {{ $p->duration }}@endif @if ($p->instructions)({{ $p->instructions }})@endif</p>
          @endforeach
        @endif
        <p><a href="{{ route('portal.care.download', $care) }}" style="color: #e89427; font-weight: bold;">⬇ Download</a></p>
      </div>
    @empty
      <p style="color: #666;">No care instructions from the clinic yet.</p>
    @endforelse
  </section>

  <section class="pet-form">
    <h2>Appointments</h2>
    @forelse ($pet->appointments as $appointment)
      <div class="history-details" @if ($loop->first) style="border-top: none; padding-top: 0;" @endif>
        <p>
          <strong>{{ $appointment->service_names ?? 'Appointment' }}</strong>
          <span class="history-status" style="margin: 0 0 0 8px;">{{ ucfirst($appointment->status) }}</span>
        </p>
        <p>{{ $appointment->appointment_date->format('F j, Y') }} at {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</p>
      </div>
    @empty
      <p style="color: #666;">No appointments yet.</p>
    @endforelse
    <div class="form-buttons">
      <a href="{{ route('portal.pets.index') }}" class="cancel-btn">Back to My Pets</a>
      <a href="{{ route('portal.appointments.create', ['pet' => $pet->id]) }}" class="primary-btn" style="text-decoration: none;">Book Appointment</a>
    </div>
  </section>

  {{-- Remove a pet that was added by mistake (only while the clinic has no records of it) --}}
  <section class="pet-form" style="margin-top: 25px; border: 1px solid #f3c9c7;">
    <h2 style="color: #b03a37;">Remove Pet</h2>
    @if ($canRemove)
      <p style="color: #666; margin: 0 0 14px;">Added {{ $pet->name }} by mistake (for example twice)? You can remove it. This cannot be undone.</p>
      <form action="{{ route('portal.pets.destroy', $pet) }}" method="post"
            onsubmit="return confirm('Remove {{ addslashes($pet->name) }} from your pets? This cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" style="height: 44px; padding: 0 20px; border: 1px solid #d9534f; border-radius: 10px; background: #fff; color: #b03a37; font-family: inherit; font-size: 15px; font-weight: bold; cursor: pointer;">Remove {{ $pet->name }}</button>
      </form>
    @else
      <p style="color: #666; margin: 0;">{{ $pet->name }} already has clinic records (appointments, visits or pet records), so it cannot be removed. Please contact the clinic if this pet was added by mistake.</p>
    @endif
  </section>
</main>
@endsection
