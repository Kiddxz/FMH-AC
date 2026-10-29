{{-- A waiver that looks like the clinic's paper CONSENT form, with ONLY the words of the paper form:
     clinic name and branches, CONSENT, the owner and pet details, the text (with the procedure in the blank),
     and "Name in Print" + "Signature". (The reference and status at the top are for the system.)
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
  .wp-paper { max-width: 840px; margin: 0 auto; overflow: hidden; border: 1px solid #e9e2d6; border-radius: 14px; background: #fff; color: #1f2937; font-family: Arial, Helvetica, sans-serif; box-shadow: 0 6px 24px rgba(38, 54, 74, 0.07); box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .wp-paper * { box-sizing: border-box; }
  .wp-top { height: 6px; background: linear-gradient(90deg, #e89427, #f5b660 50%, #26364a); }
  .wp-inner { padding: 22px 36px 30px; }
  .wp-ref { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 12px; }
  .wp-ref-no { color: #8a8f98; font-weight: 700; letter-spacing: 0.5px; }
  .wp-pill { padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; }
  .wp-pill.pending { background: #fff4e0; color: #b4690e; }
  .wp-pill.signed { background: #e7f6ec; color: #23824a; }
  .wp-pill.reviewed { background: #e8eef8; color: #2b4f86; }
  .wp-head { display: flex; align-items: center; justify-content: center; gap: 14px; padding-bottom: 10px; border-bottom: 2px solid #1f2937; }
  .wp-head img { width: 54px; height: 54px; object-fit: contain; }
  .wp-name { margin: 0; color: #1f2937; font-size: 32px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }
  .wp-branches { padding: 9px 0; border-bottom: 2px solid #1f2937; color: #374151; font-size: 11.5px; line-height: 1.75; text-align: center; }
  .wp-branches b { color: #1f2937; text-transform: uppercase; }
  .wp-title { margin: 16px 0 14px; text-align: center; }
  .wp-title span { display: inline-block; padding: 5px 26px; border: 1.5px solid #1f2937; border-radius: 999px; font-size: 15px; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; }
  .wp-box { margin-bottom: 20px; padding: 14px 16px; border: 1px solid #f0e6d8; border-radius: 10px; background: #fffbf5; }
  .wp-grid { display: grid; gap: 10px 22px; font-size: 14px; }
  .wp-grid.c2 { grid-template-columns: 2fr 1fr; }
  .wp-grid.c3 { grid-template-columns: repeat(3, 1fr); }
  .wp-grid + .wp-grid { margin-top: 10px; }
  .wp-field { display: flex; align-items: flex-end; gap: 6px; min-width: 0; }
  .wp-field span { flex-shrink: 0; color: #4b5563; }
  .wp-field b { flex: 1; min-width: 0; min-height: 20px; padding: 0 4px 2px; border-bottom: 1px solid #9ca3af; color: #111827; font-weight: 700; overflow-wrap: anywhere; }
  .wp-body p { margin: 0 0 14px; font-size: 14.5px; line-height: 1.75; text-align: justify; text-indent: 48px; }
  .wp-fill { padding: 1px 8px; border-bottom: 1.5px solid #1f2937; border-radius: 4px 4px 0 0; background: #fff1df; font-weight: 800; }
  .wp-sign { display: grid; grid-template-columns: 1.3fr 1fr; gap: 28px; margin-top: 26px; font-size: 14px; }
  .wp-sign .wp-field b { min-height: 30px; border-bottom-color: #1f2937; }
  .wp-sign img { display: block; max-width: 100%; height: 58px; margin: -30px auto -4px; object-fit: contain; }
  .wp-meta { margin: 16px 0 0; padding: 8px 12px; border-radius: 8px; background: #f0f9f3; color: #23824a; font-size: 12.5px; font-weight: 600; text-align: center; }
  @media (max-width: 640px) {
    .wp-inner { padding: 18px 14px 22px; }
    .wp-head img { width: 42px; height: 42px; }
    .wp-name { font-size: 21px; letter-spacing: 1px; }
    .wp-branches { font-size: 10.5px; }
    .wp-grid.c2, .wp-grid.c3, .wp-sign { grid-template-columns: 1fr; }
    .wp-body p { text-indent: 22px; font-size: 14px; text-align: left; }
  }
  @media print {
    .wp-paper { border: none; border-radius: 0; box-shadow: none; }
    .wp-inner { padding: 6px 0 0; }
  }
</style>
@endonce
<div class="wp-paper">
  <div class="wp-top"></div>
  <div class="wp-inner">
    <div class="wp-ref">
      <span class="wp-ref-no">{{ $waiver->reference }}</span>
      <span class="wp-pill {{ $waiver->status }}">{{ $statusText }}</span>
    </div>

    <div class="wp-head">
      <img src="{{ asset('image/fmh-logo.png') }}" alt="FMH Animal Clinic logo">
      <h2 class="wp-name">FMH Animal Clinic</h2>
    </div>
    <div class="wp-branches">
      @foreach ($branches as $branch)
        <div><b>{{ $branch['name'] }}:</b> {{ $branch['address'] }} &nbsp;Tel. No: {{ $branch['phones'][0] ?? '' }}</div>
      @endforeach
    </div>

    <div class="wp-title"><span>{{ $paper['title'] }}</span></div>

    <div class="wp-box">
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
</div>
