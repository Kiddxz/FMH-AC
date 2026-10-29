{{-- A waiver that looks like the clinic's paper CONSENT form: clinic name and branches, the owner and pet details,
     the text (with the procedure in the blank), and "Name in Print" + "Signature" at the bottom.
     Use: @include('partials.waiver-paper', ['waiver' => $waiver])
     Waivers made before the paper form (no details) show their text as it was signed. --}}
@php
  $d = $waiver->details;
  $branches = config('clinic.branches');
  $blank = '';
  // the text, with the procedure underlined in the blank (the text was escaped first)
  $body = e($waiver->content_snapshot);
  if ($waiver->procedure) {
      $body = \Illuminate\Support\Str::replaceFirst(e($waiver->procedure), '<span class="wp-fill">' . e($waiver->procedure) . '</span>', $body);
  }
  $paragraphs = preg_split("/\n\s*\n/", $body);
@endphp
@once
<style>
  .wp-paper { max-width: 820px; margin: 0 auto; padding: 34px 38px 30px; border: 1px solid #e6e1d8; border-radius: 6px; background: #fff; color: #222; font-family: Arial, Helvetica, sans-serif; box-shadow: 0 2px 10px rgba(38, 54, 74, 0.05); box-sizing: border-box; }
  .wp-ref { display: flex; justify-content: space-between; gap: 10px; margin: -14px -14px 10px; color: #8a8f98; font-size: 12px; }
  .wp-name { margin: 0; padding-bottom: 8px; border-bottom: 2px solid #222; font-size: 30px; font-weight: 900; letter-spacing: 1.5px; text-align: center; text-transform: uppercase; }
  .wp-branches { margin: 8px 0 0; padding-bottom: 8px; border-bottom: 2px solid #222; font-size: 11.5px; line-height: 1.7; text-align: center; }
  .wp-branches b { text-transform: uppercase; }
  .wp-title { margin: 14px 0 10px; font-size: 16px; font-weight: 800; letter-spacing: 1px; text-align: center; text-transform: uppercase; }
  .wp-grid { display: grid; gap: 8px 22px; margin-bottom: 18px; font-size: 14px; }
  .wp-grid.c2 { grid-template-columns: 2fr 1fr; }
  .wp-grid.c3 { grid-template-columns: repeat(3, 1fr); }
  .wp-grid + .wp-grid { margin-top: -10px; }
  .wp-field { display: flex; align-items: flex-end; gap: 6px; min-width: 0; }
  .wp-field span { flex-shrink: 0; }
  .wp-field b { flex: 1; min-width: 0; min-height: 19px; padding: 0 4px 1px; border-bottom: 1px solid #333; font-weight: 700; overflow-wrap: anywhere; }
  .wp-body p { margin: 0 0 12px; font-size: 14.5px; line-height: 1.65; text-align: justify; text-indent: 48px; }
  .wp-body.old p { text-indent: 0; text-align: left; white-space: pre-line; }
  .wp-fill { padding: 0 6px; border-bottom: 1px solid #333; font-weight: 700; }
  .wp-sign { display: grid; grid-template-columns: 1.4fr 1fr; gap: 26px; margin-top: 26px; font-size: 14px; }
  .wp-sign .wp-field b { min-height: 26px; }
  .wp-sign img { display: block; max-width: 100%; height: 58px; margin: -30px auto -4px; object-fit: contain; }
  .wp-meta { margin-top: 16px; color: #8a8f98; font-size: 12px; }
  @media (max-width: 640px) {
    .wp-paper { padding: 22px 16px 18px; }
    .wp-ref { margin: -8px -4px 8px; }
    .wp-name { font-size: 22px; }
    .wp-branches { font-size: 10.5px; }
    .wp-grid.c2, .wp-grid.c3, .wp-sign { grid-template-columns: 1fr; }
    .wp-body p { text-indent: 24px; font-size: 14px; text-align: left; }
  }
  @media print { .wp-paper { border: none; box-shadow: none; padding: 0; } }
</style>
@endonce
<div class="wp-paper">
  <div class="wp-ref">
    <span>{{ $waiver->reference }}</span>
    <span>{{ ['pending' => 'Waiting for signature', 'signed' => 'Signed', 'reviewed' => 'Reviewed'][$waiver->status] ?? $waiver->status }}</span>
  </div>
  <h2 class="wp-name">FMH Animal Clinic</h2>
  <div class="wp-branches">
    @foreach ($branches as $branch)
      <div><b>{{ $branch['name'] }}:</b> {{ $branch['address'] }} &nbsp;Tel. No: {{ $branch['phones'][0] ?? '' }}</div>
    @endforeach
  </div>
  <div class="wp-title">{{ $waiver->template?->title ?? 'Consent' }}</div>

  @if ($d)
    <div class="wp-grid c2">
      <div class="wp-field"><span>Owner's Name:</span><b>{{ $d['owner'] ?? $blank }}</b></div>
      <div class="wp-field"><span>Date:</span><b>{{ $d['date'] ?? $blank }}</b></div>
      <div class="wp-field"><span>Address:</span><b>{{ $d['address'] ?? $blank }}</b></div>
      <div class="wp-field"><span>Tel. No.</span><b>{{ $d['phone'] ?? $blank }}</b></div>
    </div>
    <div class="wp-grid c3">
      <div class="wp-field"><span>Pet's Name:</span><b>{{ $d['pet'] ?? $blank }}</b></div>
      <div class="wp-field"><span>Species/Breed:</span><b>{{ $d['species_breed'] ?? $blank }}</b></div>
      <div class="wp-field"><span>Age/Sex:</span><b>{{ $d['age_sex'] ?? $blank }}</b></div>
      <div class="wp-field"><span>Color:</span><b>{{ $d['color'] ?? $blank }}</b></div>
      <div class="wp-field"><span>Weight:</span><b>{{ $d['weight'] ?? $blank }}</b></div>
      <div class="wp-field"><span>Temp:</span><b>{{ $d['temperature'] ?? $blank }}</b></div>
    </div>
  @endif

  <div class="wp-body {{ $d ? '' : 'old' }}">
    @foreach ($paragraphs as $paragraph)
      <p>{!! trim($paragraph) !!}</p>
    @endforeach
  </div>

  <div class="wp-sign">
    <div class="wp-field"><span>Name in Print:</span><b>{{ $waiver->signer_name }}</b></div>
    <div class="wp-field"><span>Signature:</span><b>@if ($waiver->signature)<img src="{{ $waiver->signature }}" alt="Signature of {{ $waiver->signer_name }}">@endif</b></div>
  </div>
  @if ($waiver->signed_at)
    <p class="wp-meta">Signed {{ $waiver->signed_at->format('F j, Y g:i A') }} ({{ $waiver->signed_via === 'portal' ? 'online, customer portal' : 'at the clinic' }}).</p>
  @endif
</div>
