<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer dashboard with real numbers (capstone FR-REQ003).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = $request->user()->customer;

        $petCount = $customer?->pets()->count() ?? 0;

        $upcomingCount = $customer?->appointments()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('appointment_date', '>=', today())
            ->count() ?? 0;

        $completedCount = $customer?->appointments()
            ->where('status', 'completed')
            ->count() ?? 0;

        return view('customer.dashboard', compact('petCount', 'upcomingCount', 'completedCount'));
    }
}
