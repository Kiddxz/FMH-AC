{{-- Printable copy of a waiver, the same look as the clinic's paper form. The browser's print window can also "Save as PDF". --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $waiver->reference }} | FMH Animal Clinic</title>
    <style>
        body { margin: 24px auto; padding: 0 16px; background: #f5f5f5; font-family: Arial, Helvetica, sans-serif; color: #222; }
        .actions { max-width: 840px; margin: 0 auto 14px; }
        .actions button { padding: 9px 18px; border: 1px solid #222; border-radius: 6px; background: #fff; color: #222; font-weight: bold; cursor: pointer; }
        .actions button:hover { background: #222; color: #fff; }
        .review { max-width: 840px; margin: 10px auto 0; color: #888; font-size: 12px; }
        @page { size: A4; margin: 14mm; }
        @media print { body { margin: 0; padding: 0; background: #fff; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">🖨️ Print / Save as PDF</button></div>
    @include('partials.waiver-paper', ['waiver' => $waiver])
    <p class="review">
        Prepared by {{ $waiver->preparer?->full_name ?? 'FMH Animal Clinic' }} on {{ $waiver->created_at->format('F j, Y') }}.
        @if ($waiver->reviewed_at) Reviewed by Dr. {{ $waiver->reviewer?->full_name }} on {{ $waiver->reviewed_at->format('F j, Y') }}. @endif
        Printed {{ now()->format('F j, Y g:i A') }}.
    </p>
</body>
</html>
