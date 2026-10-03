<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CareInstruction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The pet owner downloads care instructions released by the vet (capstone FR-REQ007).
 * Only RELEASED instructions of the owner's OWN pets can be downloaded.
 */
class CareInstructionController extends Controller
{
    public function download(Request $request, CareInstruction $care): Response
    {
        $customer = $request->user()->customer;

        // Not released yet, or not this owner's pet: act as if it does not exist
        abort_unless($care->is_released && $customer && $care->pet?->customer_id === $customer->id, 404);

        $care->load(['pet', 'medicalRecord.veterinarian', 'medicalRecord.prescriptions']);
        $record = $care->medicalRecord;

        $lines = [
            'FMH ANIMAL CLINIC - CARE INSTRUCTIONS',
            str_repeat('=', 40),
            'Pet:          ' . $care->pet->name . ' (' . ucfirst($care->pet->species) . ')',
            'Owner:        ' . $customer->full_name,
            'Visit date:   ' . $record->record_date->format('F j, Y'),
            'Veterinarian: ' . ($record->veterinarian ? 'Dr. ' . $record->veterinarian->full_name : '-'),
            'Released:     ' . $care->released_at->format('F j, Y g:i A'),
            '',
            strtoupper($care->title),
            str_repeat('-', 40),
            $care->instructions,
        ];

        if ($record->prescriptions->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'MEDICINES TO GIVE';
            $lines[] = str_repeat('-', 40);
            foreach ($record->prescriptions as $p) {
                $lines[] = '- ' . $p->medicine_name . ': ' . $p->dosage . ', ' . $p->frequency
                    . ($p->duration ? ', for ' . $p->duration : '')
                    . ($p->instructions ? ' (' . $p->instructions . ')' : '');
            }
        }

        $lines[] = '';
        $lines[] = 'If you have questions or your pet does not get better, please call FMH Animal Clinic.';

        $filename = 'care-instructions-' . str($care->pet->name)->slug() . '-' . $care->released_at->format('Y-m-d') . '.txt';

        return response(implode("\r\n", $lines) . "\r\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
