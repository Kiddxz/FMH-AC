<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Pet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "My Pets" in the customer portal (capstone FR-REQ005 / FR-REQ006).
 * A customer only sees and edits THEIR OWN pets (PetPolicy, Phase 4).
 * Pets are not deleted by customers: their medical history must be kept.
 */
class PetController extends Controller
{
    public function index(Request $request): View
    {
        $pets = $this->customer($request)->pets()->orderBy('name')->get();

        return view('customer.pets.index', compact('pets'));
    }

    public function create(): View
    {
        return view('customer.pets.create', ['pet' => new Pet()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Pet::class);
        $data = $this->validatePet($request);

        $pet = new Pet($this->toColumns($data));
        $pet->customer_id = $this->customer($request)->id;   // always the logged-in owner
        $pet->save();

        ActivityLog::record('created', 'Pets', 'Added pet ' . $pet->name . '.', $pet);

        return redirect()->route('portal.pets.index')->with('status', $pet->name . ' was added to your pets.');
    }

    public function show(Pet $pet): View
    {
        Gate::authorize('view', $pet);

        $pet->load([
            'vaccinations' => fn ($q) => $q->orderByDesc('date_given'),
            'careInstructions' => fn ($q) => $q->released()->with('medicalRecord.prescriptions')->latest('released_at'),
            'appointments' => fn ($q) => $q->with('service')->orderByDesc('appointment_date')->orderByDesc('appointment_time'),
        ]);

        return view('customer.pets.show', compact('pet'));
    }

    public function edit(Pet $pet): View
    {
        Gate::authorize('update', $pet);

        return view('customer.pets.edit', compact('pet'));
    }

    public function update(Request $request, Pet $pet): RedirectResponse
    {
        Gate::authorize('update', $pet);
        $data = $this->validatePet($request);

        $columns = $this->toColumns($data);
        // Keep the saved birthdate if the age was not changed
        if ((int) $data['age'] === $pet->age_years) {
            unset($columns['birthdate']);
        }

        $pet->update($columns);
        ActivityLog::record('updated', 'Pets', 'Updated pet ' . $pet->name . '.', $pet);

        return redirect()->route('portal.pets.show', $pet)->with('status', 'The details of ' . $pet->name . ' were saved.');
    }

    // ---------- helpers ----------

    private function customer(Request $request): Customer
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 403, 'Your account has no pet owner profile. Please contact the clinic.');

        return $customer;
    }

    private function validatePet(Request $request): array
    {
        return $request->validate([
            'petname' => ['required', 'string', 'max:100'],
            'species' => ['required', Rule::in(Pet::SPECIES)],
            'breed' => ['required', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'min:0', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'petname' => 'pet name',
            'sex' => 'sex',
        ]);
    }

    // Form field names -> database columns. Age is saved as an approximate birthdate,
    // so the age shown keeps going up every year.
    private function toColumns(array $data): array
    {
        return [
            'name' => $data['petname'],
            'species' => $data['species'],
            'breed' => $data['breed'],
            'gender' => $data['sex'],
            'birthdate' => now()->subYears((int) $data['age'])->toDateString(),
            'color' => $data['color'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
