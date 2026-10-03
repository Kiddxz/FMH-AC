<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Clinic services and their official prices (capstone FR-REQ013).
 * The same list is used by online booking (Phase 9) and the POS (Phase 14).
 */
class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $purpose = $request->query('purpose');

        $services = Service::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when(in_array($purpose, Service::PURPOSES, true), fn ($q) => $q->where('purpose', $purpose))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.services.index', compact('services', 'search', 'purpose'));
    }

    public function create(): View
    {
        return view('admin.services.form', ['service' => new Service(['is_active' => true, 'duration_minutes' => 30])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $service = Service::create($this->validateService($request));
        ActivityLog::record('created', 'Services', 'Added service ' . $service->name . ' (₱' . number_format($service->price, 2) . ').', $service);

        return redirect()->route('admin.services.index')->with('status', $service->name . ' was added.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', compact('service'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $oldPrice = $service->price;
        $service->update($this->validateService($request, $service));

        $note = (float) $oldPrice !== (float) $service->price
            ? ' Price changed from ₱' . number_format($oldPrice, 2) . ' to ₱' . number_format($service->price, 2) . '.'
            : '';
        ActivityLog::record('updated', 'Services', 'Updated service ' . $service->name . '.' . $note, $service);

        return redirect()->route('admin.services.index')->with('status', $service->name . ' was updated.');
    }

    // Activate / deactivate: an inactive service cannot be booked or sold, but its history stays
    public function toggle(Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);
        $state = $service->is_active ? 'activated' : 'deactivated';
        ActivityLog::record('updated', 'Services', ucfirst($state) . ' service ' . $service->name . '.', $service);

        return back()->with('status', $service->name . ' was ' . $state . '.');
    }

    // A service that was already used is kept for the records; it can only be deactivated
    public function destroy(Service $service): RedirectResponse
    {
        $used = $service->appointments()->exists()
            || DB::table('patient_visits')->where('service_id', $service->id)->exists()
            || DB::table('transaction_items')->where('service_id', $service->id)->exists();

        if ($used) {
            return back()->withErrors([
                'service' => $service->name . ' is already used in appointments or transactions, so it cannot be deleted. Deactivate it instead.',
            ]);
        }

        $name = $service->name;
        ActivityLog::record('deleted', 'Services', 'Deleted service ' . $name . '.');
        $service->delete();

        return redirect()->route('admin.services.index')->with('status', $name . ' was deleted.');
    }

    private function validateService(Request $request, ?Service $service = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('services', 'name')->ignore($service?->id)],
            'purpose' => ['required', Rule::in(Service::PURPOSES)],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.unique' => 'A service with this name already exists.',
        ], [
            'duration_minutes' => 'duration',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
