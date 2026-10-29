{{-- Printable copy of a waiver, the same look as the clinic's paper form. The browser's print window can also "Save as PDF". --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $waiver->reference }} | FMH Animal Clinic</title>
    <style>
        body { margin: 24px auto; padding: 0 16px; background: #f4f1ec; font-family: Arial, Helvetica, sans-serif; color: #222; }
        .actions { max-width: 820px; margin: 0 auto 14px; }
        .actions button { padding: 10px 18px; border: none; border-radius: 8px; background: #e89427; color: #fff; font-weight: bold; cursor: pointer; }
        .review { max-width: 820px; margin: 10px auto 0; color: #666; font-size: 12px; }
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
