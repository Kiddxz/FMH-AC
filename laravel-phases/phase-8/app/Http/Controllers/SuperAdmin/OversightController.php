<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * READ-ONLY monitoring pages for the Super Admin (decision P1).
 * Nothing here can be changed, and medical notes are not shown.
 */
class OversightController extends Controller
{
    public function appointments(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        $appointments = Appointment::with(['customer', 'pet', 'service'])
            ->when(in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('pet', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                ->orWhereHas('service', fn ($s) => $s->where('name', 'like', "%{$search}%"))))
            ->orderByDesc('appointment_date')->orderByDesc('appointment_time')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.appointments', compact('appointments', 'search', 'status'));
    }

    public function petRecords(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $species = $request->query('species');

        $pets = Pet::with('customer')
            ->withCount(['medicalRecords', 'vaccinations'])
            ->when(in_array($species, Pet::SPECIES, true), fn ($q) => $q->where('species', $species))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.pet-records', compact('pets', 'search', 'species'));
    }

    public function inventory(Request $request): View
    {
        $category = $request->query('category');

        $items = InventoryItem::query()
            ->withSum('batches as stock_total', 'quantity')
            ->withMin(['batches as next_expiry' => fn ($q) => $q->where('quantity', '>', 0)], 'expiration_date')
            ->when(in_array($category, InventoryItem::CATEGORIES, true), fn ($q) => $q->where('category', $category))
            ->orderBy('category')->orderBy('name')
            ->get();

        return view('superadmin.inventory', compact('items', 'category'));
    }

    public function activityLogs(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $module = $request->query('module');

        $logs = ActivityLog::with('user')
            ->when($module, fn ($q) => $q->where('module', $module))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('description', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))))
            ->latest('created_at')->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('superadmin.activity-logs', [
            'logs' => $logs,
            'modules' => ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module'),
            'search' => $search,
            'module' => $module,
        ]);
    }
}
