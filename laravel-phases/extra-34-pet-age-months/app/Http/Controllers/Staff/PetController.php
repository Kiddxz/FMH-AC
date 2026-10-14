<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pet list and pet profiles for the front desk (capstone FR-REQ003 / FR-REQ008 / FR-REQ010).
 * Staff see the VACCINATION history only; full medical notes are for the vet (decision P4).
 */
class PetController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $species = $request->query('species');

        $pets = Pet::with('customer')
            ->when(in_array($species, Pet::SPECIES, true), fn ($q) => $q->where('species', $species))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('breed', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('contact_number', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('staff.pets.index', compact('pets', 'search', 'species'));
    }

    public function show(Pet $pet): View
    {
        Gate::authorize('view', $pet);

        $pet->load([
            'customer',
            'vaccinations' => fn ($q) => $q->orderByDesc('date_given'),
            'appointments' => fn ($q) => $q->with(['service', 'veterinarian'])->orderByDesc('appointment_date'),
        ]);

        return view('staff.pets.show', compact('pet'));
    }

    public function edit(Pet $pet): View
    {
        Gate::authorize('update', $pet);

        return view('staff.pets.edit', compact('pet'));
    }

    public function update(Request $request, Pet $pet): RedirectResponse
    {
        Gate::authorize('update', $pet);

        $data = $request->validate([
            'petname' => ['required', 'string', 'max:100'],
            'species' => ['required', Rule::in(Pet::SPECIES)],
            'breed' => ['required', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'min:0', 'max:50'],
            'age_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'color' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'deceased', 'archived'])],
        ], [], ['petname' => 'pet name', 'age' => 'age (years)', 'age_months' => 'age (months)']);

        $columns = [
            'name' => $data['petname'],
            'species' => $data['species'],
            'breed' => $data['breed'],
            'gender' => $data['sex'],
            'color' => $data['color'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'],
        ];
        // Only move the birthdate when the age was changed
        $months = (int) ($data['age_months'] ?? $pet->age_months);   // no months sent: keep the current months
        if ((int) $data['age'] !== $pet->age_years || $months !== $pet->age_months) {
            $columns['birthdate'] = Pet::birthdateFromAge((int) $data['age'], $months);
        }

        $pet->update($columns);
        ActivityLog::record('updated', 'Pets', 'Updated pet profile of ' . $pet->name . '.', $pet);

        return redirect()->route('staff.pets.show', $pet)->with('status', 'The pet profile was saved.');
    }
}
