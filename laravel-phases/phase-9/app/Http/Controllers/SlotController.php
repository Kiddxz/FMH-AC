<?php

namespace App\Http\Controllers;

use App\Services\AppointmentScheduler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns the time slots of one day as JSON, for the booking forms.
 * Example: /appointments/slots?date=2026-10-05  ->  {"open": true, "slots": [...]}
 */
class SlotController extends Controller
{
    public function __invoke(Request $request, AppointmentScheduler $scheduler): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'ignore' => ['nullable', 'integer'],   // the appointment being rescheduled
        ]);

        $slots = $scheduler->slotsFor($data['date'], $data['ignore'] ?? null);

        return response()->json([
            'open' => count($slots) > 0,
            'slots' => $slots,
        ]);
    }
}
