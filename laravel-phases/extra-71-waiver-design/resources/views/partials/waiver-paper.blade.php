{{-- A waiver that looks like the clinic's paper CONSENT form: clinic name and branches, the owner and pet details,
     the authorization (with the procedure in the blank), the Terms and Conditions, and "Name in Print" + "Signature".
     Use: @include('partials.waiver-paper', ['waiver' => $waiver])
     Waivers made before the paper form show the same design: their details are read from the top of their text. --}}
@php
  $paper = $waiver->paper();
  $d = $paper['details'];
  $branches = config('clinic.branches');
  // the text, with the procedure marked in the blank (the text was escaped first)
  $body = e($paper['body']);
  if ($waiver->procedure) {
      $body = \Illuminate\Support\Str::replaceFirst(e($waiver->procedure), '<span class="wp-fill">' . e($waiver->procedure) . '</span>', $body);
  }
  $paragraphs = array_values(array_filter(array_map('trim', preg_split("/\n\s*\n/", $body))));
  // (the labels below are fixed words of the form, written as they are)
  // first paragraph = the authorization; the next ones = the Terms and Conditions of the clinic
  $authorization = array_shift($paragraphs);
  $terms = $paragraphs;
  $statusText = ['pending' => 'Waiting for signature', 'signed' => 'Signed', 'reviewed' => 'Reviewed'][$waiver->status] ?? $waiver->status;
  $fields = [
      ["Owner's Name:", $d['owner'] ?? null, 'wide'],
      ['Tel. No.', $d['phone'] ?? null, ''],
      ['Address:', $d['address'] ?? null, 'wide'],
      ['Date:', $d['date'] ?? null, ''],
  ];
  $petFields = [
      ["Pet's Name:", $d['pet'] ?? null],
      ['Species/Breed:', $d['species_breed'] ?? null],
      ['Age/Sex:', $d['age_sex'] ?? null],
      ['Color:', $d['color'] ?? null],
      ['Weight:', $d['weight'] ?? null],
      ['Temp:', $d['temperature'] ?? null],
  ];
@endphp
@once
<style>
  .wp-paper { position: relative; max-width: 840px; margin: 0 auto; overflow: hidden; border: 1px solid #e9e2d6; border-radius: 14px; background: #fff; color: #1f2937; font-family: Arial, Helvetica, sans-serif; box-shadow: 0 6px 24px rgba(38, 54, 74, 0.07); box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .wp-paper * { box-sizing: border-box; }
  .wp-top { height: 6px; background: linear-gradient(90deg, #e89427, #f5b660 50%, #26364a); }
  .wp-inner { padding: 26px 34px 26px; }
  .wp-ref { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 14px; font-size: 12px; }
  .wp-ref-no { color: #8a8f98; font-weight: 700; letter-spacing: 0.5px; }
  .wp-pill { padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; }
  .wp-pill.pending { background: #fff4e0; color: #b4690e; }
  .wp-pill.signed { background: #e7f6ec; color: #23824a; }
  .wp-pill.reviewed { background: #e8eef8; color: #2b4f86; }
  .wp-head { display: flex; align-items: center; justify-content: center; gap: 14px; padding-bottom: 12px; border-bottom: 2px solid #1f2937; }
  .wp-head img { width: 58px; height: 58px; object-fit: contain; }
  .wp-name { margin: 0; color: #1f2937; font-size: 30px; font-weight: 900; letter-spacing: 2px; line-height: 1.1; text-transform: uppercase; }
  .wp-name small { display: block; margin-top: 4px; color: #8a8f98; font-size: 11.5px; font-weight: 600; letter-spacing: 1.5px; }
  .wp-branches { padding: 9px 0; border-bottom: 2px solid #1f2937; font-size: 11.5px; line-height: 1.75; text-align: center; color: #374151; }
  .wp-branches b { color: #1f2937; text-transform: uppercase; }
  .wp-title { display: flex; justify-content: center; margin: 18px 0 16px; }
  .wp-title span { padding: 6px 26px; border: 1.5px solid #1f2937; border-radius: 999px; font-size: 15px; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; }
  .wp-section { margin-bottom: 16px; }
  .wp-label { display: flex; align-items: center; gap: 8px; margin: 0 0 8px; color: #c46f0c; font-size: 11.5px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; }
  .wp-label::after { content: ""; flex: 1; height: 1px; background: #f1e4d2; }
  .wp-box { padding: 14px 16px; border: 1px solid #f0e6d8; border-radius: 10px; background: #fffbf5; }
  .wp-grid { display: grid; gap: 10px 22px; font-size: 14px; }
  .wp-grid.c2 { grid-template-columns: 2fr 1fr; }
  .wp-grid.c3 { grid-template-columns: repeat(3, 1fr); }
  .wp-grid + .wp-grid { margin-top: 10px; }
  .wp-field { display: flex; align-items: flex-end; gap: 6px; min-width: 0; }
  .wp-field span { flex-shrink: 0; color: #4b5563; }
  .wp-field b { flex: 1; min-width: 0; min-height: 20px; padding: 0 4px 2px; border-bottom: 1px solid #9ca3af; color: #111827; font-weight: 700; overflow-wrap: anywhere; }
  .wp-text { margin: 0; font-size: 14.5px; line-height: 1.75; text-align: justify; text-indent: 44px; }
  .wp-fill { padding: 1px 8px; border-bottom: 1.5px solid #1f2937; border-radius: 4px 4px 0 0; background: #fff1df; font-weight: 800; }
  .wp-terms { margin: 0; padding: 0; list-style: none; counter-reset: term; border: 1px solid #ece4d8; border-left: 4px solid #e89427; border-radius: 10px; background: #fff; }
  .wp-terms li { position: relative; padding: 12px 16px 12px 52px; font-size: 14px; line-height: 1.7; text-align: justify; counter-increment: term; }
  .wp-terms li + li { border-top: 1px dashed #ece4d8; }
  .wp-terms li::before { content: counter(term); position: absolute; left: 16px; top: 13px; display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #26364a; color: #fff; font-size: 12px; font-weight: 800; }
  .wp-agree { margin: 12px 0 0; color: #4b5563; font-size: 13px; font-style: italic; text-align: center; }
  .wp-sign { display: grid; grid-template-columns: 1.3fr 1fr; gap: 28px; margin-top: 8px; font-size: 14px; }
  .wp-sign .wp-field b { min-height: 30px; border-bottom-color: #1f2937; }
  .wp-sign img { display: block; max-width: 100%; height: 58px; margin: -30px auto -4px; object-fit: contain; }
  .wp-sign small { display: block; margin-top: 4px; color: #8a8f98; font-size: 11.5px; text-align: center; }
  .wp-meta { display: flex; align-items: center; justify-content: center; gap: 6px; margin: 16px 0 0; padding: 8px 12px; border-radius: 8px; background: #f0f9f3; color: #23824a; font-size: 12.5px; font-weight: 600; }
  .wp-foot { padding: 10px 34px; border-top: 1px solid #f1ebe2; background: #faf7f2; color: #8a8f98; font-size: 11.5px; text-align: center; }
  @media (max-width: 640px) {
    .wp-inner { padding: 18px 14px; }
    .wp-head img { width: 44px; height: 44px; }
    .wp-name { font-size: 21px; letter-spacing: 1px; }
    .wp-branches { font-size: 10.5px; }
    .wp-grid.c2, .wp-grid.c3, .wp-sign { grid-template-columns: 1fr; }
    .wp-text, .wp-terms li { text-align: left; }
    .wp-text { text-indent: 20px; font-size: 14px; }
    .wp-terms li { padding-left: 46px; }
    .wp-foot { padding: 10px 14px; }
  }
  @media print {
    .wp-paper { border: none; border-radius: 0; box-shadow: none; }
    .wp-inner { padding: 6px 0 0; }
    .wp-foot { padding: 8px 0; }
    .wp-section, .wp-terms li, .wp-sign { break-inside: avoid; }
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
      <h2 class="wp-name">FMH Animal Clinic<small>Caring for your pets like family</small></h2>
    </div>
    <div class="wp-branches">
      @foreach ($branches as $branch)
        <div><b>{{ $branch['name'] }}:</b> {{ $branch['address'] }} &nbsp;Tel. No: {{ $branch['phones'][0] ?? '' }}</div>
      @endforeach
    </div>

    <div class="wp-title"><span>{{ $paper['title'] }}</span></div>

    <div class="wp-section">
      <p class="wp-label">Owner &amp; Pet Information</p>
      <div class="wp-box">
        <div class="wp-grid c2">
          @foreach ($fields as [$label, $value])
            <div class="wp-field"><span>{!! $label !!}</span><b>{{ $value }}</b></div>
          @endforeach
        </div>
        <div class="wp-grid c3">
          @foreach ($petFields as [$label, $value])
            <div class="wp-field"><span>{!! $label !!}</span><b>{{ $value }}</b></div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="wp-section">
      <p class="wp-label">{{ $terms ? 'Authorization' : 'Consent' }}</p>
      <p class="wp-text">{!! $authorization !!}</p>
    </div>

    @if ($terms)
      <div class="wp-section">
        <p class="wp-label">Terms and Conditions</p>
        <ol class="wp-terms">
          @foreach ($terms as $term)
            <li>{!! $term !!}</li>
          @endforeach
        </ol>
        <p class="wp-agree">By signing below, I confirm that I have read, understood and agreed to all of the above.</p>
      </div>
    @endif

    <div class="wp-section">
      <p class="wp-label">Signature</p>
      <div class="wp-sign">
        <div><div class="wp-field"><span>Name in Print:</span><b>{{ $waiver->signer_name }}</b></div><small>Owner / authorized agent</small></div>
        <div><div class="wp-field"><span>Signature:</span><b>@if ($waiver->signature)<img src="{{ $waiver->signature }}" alt="Signature of {{ $waiver->signer_name }}">@endif</b></div><small>{{ $waiver->signed_at ? $waiver->signed_at->format('F j, Y') : 'Date signed' }}</small></div>
      </div>
      @if ($waiver->signed_at)
        <p class="wp-meta">✔ Signed {{ $waiver->signed_at->format('F j, Y g:i A') }} ({{ $waiver->signed_via === 'portal' ? 'online, customer portal' : 'at the clinic' }})</p>
      @endif
    </div>
  </div>
  <div class="wp-foot">This form is part of the records of FMH Animal Clinic · {{ $waiver->reference }}</div>
</div>
