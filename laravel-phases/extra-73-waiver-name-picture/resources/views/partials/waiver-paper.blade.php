{{-- A waiver that looks like the clinic's paper CONSENT form (black and white, like the paper):
     the logo and the clinic name (the picture of the name from the paper form) and branches, CONSENT,
     the owner and pet details, the text (with the procedure in the blank), and "Name in Print" + "Signature".
     The reference and status at the top are for the system.
     Use: @include('partials.waiver-paper', ['waiver' => $waiver])
     Waivers made before the paper form show the same design: their details are read from the top of their text. --}}
@php
  $paper = $waiver->paper();
  $d = $paper['details'];
  $branches = config('clinic.branches');
  // the text, with the procedure written in the blank (the text was escaped first)
  $body = e($paper['body']);
  if ($waiver->procedure) {
      $body = \Illuminate\Support\Str::replaceFirst(e($waiver->procedure), '<span class="wp-fill">' . e($waiver->procedure) . '</span>', $body);
  }
  $paragraphs = array_values(array_filter(array_map('trim', preg_split("/\n\s*\n/", $body))));
  $statusText = ['pending' => 'Waiting for signature', 'signed' => 'Signed', 'reviewed' => 'Reviewed'][$waiver->status] ?? $waiver->status;
@endphp
@once
<style>
  .wp-paper { max-width: 840px; margin: 0 auto; padding: 22px 38px 30px; border: 1px solid #d9d9d9; border-radius: 4px; background: #fff; color: #222; font-family: Arial, Helvetica, sans-serif; box-sizing: border-box; }
  .wp-paper * { box-sizing: border-box; }
  .wp-ref { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 10px; color: #999; font-size: 11.5px; }
  .wp-head { display: flex; align-items: center; justify-content: center; gap: 12px; padding-bottom: 6px; border-bottom: 1.5px solid #222; }
  .wp-head img { width: 50px; height: 50px; object-fit: contain; }
  .wp-name { margin: 0; line-height: 0; }
  .wp-name img { display: block; width: 340px; max-width: 64vw; height: auto; }
  .wp-branches { padding: 8px 0; border-bottom: 1.5px solid #222; color: #333; font-size: 11.5px; line-height: 1.75; text-align: center; }
  .wp-branches b { font-weight: 400; text-transform: uppercase; }
  .wp-title { margin: 14px 0 12px; font-size: 15px; font-weight: 700; letter-spacing: 1px; text-align: center; text-transform: uppercase; }
  .wp-grid { display: grid; gap: 9px 22px; font-size: 14px; }
  .wp-grid.c2 { grid-template-columns: 2fr 1fr; }
  .wp-grid.c3 { grid-template-columns: repeat(3, 1fr); }
  .wp-grid + .wp-grid { margin-top: 9px; }
  .wp-field { display: flex; align-items: flex-end; gap: 6px; min-width: 0; }
  .wp-field span { flex-shrink: 0; color: #444; }
  .wp-field b { flex: 1; min-width: 0; min-height: 20px; padding: 0 4px 2px; border-bottom: 1px solid #555; color: #111; font-weight: 700; overflow-wrap: anywhere; }
  .wp-body { margin-top: 20px; }
  .wp-body p { margin: 0 0 13px; font-size: 14.5px; line-height: 1.7; text-align: justify; text-indent: 48px; }
  .wp-fill { padding: 0 6px; border-bottom: 1px solid #222; font-weight: 700; }
  .wp-sign { display: grid; grid-template-columns: 1.3fr 1fr; gap: 28px; margin-top: 26px; font-size: 14px; }
  .wp-sign .wp-field b { min-height: 30px; border-bottom-color: #222; }
  .wp-sign img { display: block; max-width: 100%; height: 58px; margin: -30px auto -4px; object-fit: contain; }
  .wp-meta { margin: 14px 0 0; color: #888; font-size: 12px; text-align: right; }
  @media (max-width: 640px) {
    .wp-paper { padding: 18px 14px 22px; }
    .wp-head img { width: 40px; height: 40px; }
    .wp-name img { width: 230px; max-width: 62vw; }
    .wp-branches { font-size: 10.5px; }
    .wp-grid.c2, .wp-grid.c3, .wp-sign { grid-template-columns: 1fr; }
    .wp-body p { text-indent: 22px; font-size: 14px; text-align: left; }
    .wp-meta { text-align: left; }
  }
  @media print { .wp-paper { border: none; padding: 0; } }
</style>
@endonce
<div class="wp-paper">
  <div class="wp-ref">
    <span>{{ $waiver->reference }}</span>
    <span>{{ $statusText }}</span>
  </div>

  <div class="wp-head">
    <img src="{{ asset('image/fmh-logo.png') }}" alt="FMH Animal Clinic logo">
    <h2 class="wp-name"><img src="{{ asset('image/fmh-name.png') }}" alt="FMH Animal Clinic"></h2>
  </div>
  <div class="wp-branches">
    @foreach ($branches as $branch)
      <div><b>{{ $branch['name'] }}:</b> {{ $branch['address'] }} &nbsp;Tel. No: {{ $branch['phones'][0] ?? '' }}</div>
    @endforeach
  </div>

  <div class="wp-title">{{ $paper['title'] }}</div>

  <div class="wp-grid c2">
    <div class="wp-field"><span>Owner's Name:</span><b>{{ $d['owner'] ?? '' }}</b></div>
    <div class="wp-field"><span>Date:</span><b>{{ $d['date'] ?? '' }}</b></div>
    <div class="wp-field"><span>Address:</span><b>{{ $d['address'] ?? '' }}</b></div>
    <div class="wp-field"><span>Tel. No.</span><b>{{ $d['phone'] ?? '' }}</b></div>
  </div>
  <div class="wp-grid c3">
    <div class="wp-field"><span>Pet's Name:</span><b>{{ $d['pet'] ?? '' }}</b></div>
    <div class="wp-field"><span>Species/Breed:</span><b>{{ $d['species_breed'] ?? '' }}</b></div>
    <div class="wp-field"><span>Age/Sex:</span><b>{{ $d['age_sex'] ?? '' }}</b></div>
    <div class="wp-field"><span>Color:</span><b>{{ $d['color'] ?? '' }}</b></div>
    <div class="wp-field"><span>Weight:</span><b>{{ $d['weight'] ?? '' }}</b></div>
    <div class="wp-field"><span>Temp:</span><b>{{ $d['temperature'] ?? '' }}</b></div>
  </div>

  <div class="wp-body">
    @foreach ($paragraphs as $paragraph)
      <p>{!! $paragraph !!}</p>
    @endforeach
  </div>

  <div class="wp-sign">
    <div class="wp-field"><span>Name in Print:</span><b>{{ $waiver->signer_name }}</b></div>
    <div class="wp-field"><span>Signature:</span><b>@if ($waiver->signature)<img src="{{ $waiver->signature }}" alt="Signature of {{ $waiver->signer_name }}">@endif</b></div>
  </div>
  @if ($waiver->signed_at)
    <p class="wp-meta">Signed {{ $waiver->signed_at->format('F j, Y g:i A') }} ({{ $waiver->signed_via === 'portal' ? 'online, customer portal' : 'at the clinic' }})</p>
  @endif
</div>
