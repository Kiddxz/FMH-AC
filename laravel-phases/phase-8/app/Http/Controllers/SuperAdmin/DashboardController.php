<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\View\View;

/**
 * Super Admin dashboard: system-wide numbers and the latest activity (capstone FR-REQ004).
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('superadmin.dashboard', [
            'userCount' => User::count(),
            'appointmentCount' => Appointment::count(),
            'transactionCount' => Transaction::count(),
            'itemCount' => InventoryItem::where('is_active', true)->count(),
            'recentActivity' => ActivityLog::with('user')->latest('created_at')->latest('id')->limit(6)->get(),
        ]);
    }
}
