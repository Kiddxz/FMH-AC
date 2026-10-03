<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use Illuminate\View\View;

/**
 * Veterinarian / Admin dashboard with real numbers (capstone FR-REQ003).
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'todayCount' => Appointment::whereDate('appointment_date', today())->where('status', '!=', 'cancelled')->count(),
            'petCount' => Pet::where('status', 'active')->count(),
            'ownerCount' => Customer::count(),
            'pendingCount' => Appointment::where('status', 'pending')->whereDate('appointment_date', '>=', today())->count(),
            // Today and the coming days first, then the most recent past ones
            'recentAppointments' => Appointment::with(['customer', 'pet', 'service'])
                ->orderByRaw('appointment_date < ? asc', [today()->toDateString()])
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->limit(6)
                ->get(),
        ]);
    }
}
