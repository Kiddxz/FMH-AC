<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Supplier records, managed by Staff (capstone FR-REQ017, decision P3).
 * Suppliers are deactivated instead of deleted, because deliveries point to them.
 */
class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $suppliers = Supplier::withCount('items')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('contact_person', 'like', "%{$search}%")))
            ->orderByDesc('is_active')->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('staff.suppliers.index', compact('suppliers', 'search'));
    }

    public function create(): View
    {
        return view('staff.suppliers.form', ['supplier' => new Supplier(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));
        ActivityLog::record('created', 'Suppliers', 'Added supplier ' . $supplier->name . '.', $supplier);

        return redirect()->route('staff.suppliers.index')->with('status', $supplier->name . ' was added.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('staff.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request, $supplier));
        ActivityLog::record('updated', 'Suppliers', 'Updated supplier ' . $supplier->name . '.', $supplier);

        return redirect()->route('staff.suppliers.index')->with('status', $supplier->name . ' was saved.');
    }

    public function toggle(Supplier $supplier): RedirectResponse
    {
        $supplier->is_active = ! $supplier->is_active;
        $supplier->save();
        ActivityLog::record($supplier->is_active ? 'activated' : 'deactivated', 'Suppliers',
            ($supplier->is_active ? 'Activated' : 'Deactivated') . ' supplier ' . $supplier->name . '.', $supplier);

        return back()->with('status', $supplier->name . ' is now ' . ($supplier->is_active ? 'active' : 'inactive') . '.');
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($supplier)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'regex:/^(09\d{9}|0\d{1,2}\d{7,8})$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ], [
            'name.unique' => 'A supplier with this name already exists.',
            'contact_number.regex' => 'Enter a mobile number (09171234567) or a landline with area code (0281234567).',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
