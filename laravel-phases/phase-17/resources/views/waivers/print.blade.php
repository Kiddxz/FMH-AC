{{-- Printable copy of a waiver. The browser's print window can also "Save as PDF". --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $waiver->reference }} | FMH Animal Clinic</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; max-width: 760px; margin: 30px auto; padding: 0 20px; line-height: 1.6; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #e89427; padding-bottom: 12px; margin-bottom: 20px; }
        .head h1 { margin: 0; font-size: 22px; }
        .muted { color: #666; font-size: 13px; }
        .text { white-space: pre-line; border: 1px solid #ddd; border-radius: 6px; padding: 18px; }
        .sign { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 40px; }
        .line { border-top: 1px solid #333; padding-top: 6px; font-size: 13px; }
        .stamp { font-weight: bold; font-size: 15px; min-height: 22px; }
        .actions { margin: 20px 0; }
        .actions button { padding: 10px 18px; background: #e89427; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">🖨️ Print / Save as PDF</button></div>
    <div class="head">
        <div>
            <h1>🐾 {{ \App\Models\Setting::get('clinic_name') }}</h1>
            <div class="muted">{{ \App\Models\Setting::get('clinic_address') }} · {{ \App\Models\Setting::get('clinic_contact') }}</div>
            <div class="muted">Digital Waiver &amp; Consent Form</div>
        </div>
        <div style="text-align: right;">
            <strong>{{ $waiver->reference }}</strong>
            <div class="muted">Status: {{ ['pending' => 'Waiting for signature', 'signed' => 'Signed', 'reviewed' => 'Reviewed'][$waiver->status] }}</div>
        </div>
    </div>

    <div class="text">{{ $waiver->content_snapshot }}</div>

    <div class="sign">
        <div>
            <div class="stamp">{{ $waiver->signer_name }}</div>
            <div class="line">Signature of owner / representative
                @if ($waiver->signed_at)<br>Signed {{ $waiver->signed_at->format('F j, Y g:i A') }} ({{ $waiver->signed_via === 'portal' ? 'customer portal' : 'at the clinic' }})@endif
            </div>
        </div>
        <div>
            <div class="stamp">{{ $waiver->reviewer ? 'Dr. ' . $waiver->reviewer->full_name : '' }}</div>
            <div class="line">Veterinarian
                @if ($waiver->reviewed_at)<br>Reviewed {{ $waiver->reviewed_at->format('F j, Y g:i A') }}@endif
            </div>
        </div>
    </div>
    <p class="muted" style="margin-top: 30px;">Prepared by {{ $waiver->preparer?->full_name ?? \App\Models\Setting::get('clinic_name') }} on {{ $waiver->created_at->format('F j, Y') }}. Printed {{ now()->format('F j, Y g:i A') }}.</p>
</body>
</html>
