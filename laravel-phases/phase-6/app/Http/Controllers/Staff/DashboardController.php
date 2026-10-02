<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Pet;
use Illuminate\View\View;

/**
 * Staff / Receptionist dashboard with real numbers (capstone FR-REQ003).
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = Appointment::with(['customer', 'pet', 'service'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->get();

        return view('staff.dashboard', [
            'todaysAppointments' => $today,
            'todayCount' => $today->where('status', '!=', 'cancelled')->count(),
            'pendingCount' => Appointment::where('status', 'pending')->whereDate('appointment_date', '>=', today())->count(),
            'petCount' => Pet::where('status', 'active')->count(),
            'completedTodayCount' => $today->where('status', 'completed')->count(),
        ]);
    }
}
