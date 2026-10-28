<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Transaction;
use App\Models\Waiver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "My Pets" in the customer portal (capstone FR-REQ005 / FR-REQ006).
 * A customer only sees and edits THEIR OWN pets (PetPolicy, Phase 4).
 * A pet added by mistake can be removed by the owner, but only while it has no clinic records
 * (appointments, visits, pet records, vaccinations, bills, waivers): that history must be kept.
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

        // Stop the same pet from being added twice by mistake (e.g. the Save button was clicked twice)
        $customer = $this->customer($request);
        $sameName = $customer->pets()->where('species', $data['species'])->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['petname']))])->exists();
        if ($sameName) {
            return back()->withInput()->withErrors(['petname' => 'You already have a ' . $data['species'] . ' named ' . trim($data['petname']) . '. Please check My Pets.']);
        }

        $pet = new Pet($this->toColumns($data));
        $pet->customer_id = $customer->id;   // always the logged-in owner
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

        return view('customer.pets.show', ['pet' => $pet, 'canRemove' => ! $this->hasClinicHistory($pet)]);
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

        // No months sent: keep the pet's current months
        $data['age_months'] ??= $pet->age_months;
        $columns = $this->toColumns($data);
        // Keep the saved birthdate if the age was not changed
        if ((int) $data['age'] === $pet->age_years && (int) $data['age_months'] === $pet->age_months) {
            unset($columns['birthdate']);
        }

        $pet->update($columns);
        ActivityLog::record('updated', 'Pets', 'Updated pet ' . $pet->name . '.', $pet);

        return redirect()->route('portal.pets.show', $pet)->with('status', 'The details of ' . $pet->name . ' were saved.');
    }

    // Remove a pet that was added by mistake (only while it has no clinic records)
    public function destroy(Pet $pet): RedirectResponse
    {
        Gate::authorize('update', $pet);   // only the owner

        if ($this->hasClinicHistory($pet)) {
            return redirect()->route('portal.pets.show', $pet)->withErrors(['pet' => $pet->name
                . ' already has clinic records, so it cannot be removed. Please contact the clinic if this pet was added by mistake.']);
        }

        $pet->delete();   // soft delete: the row stays in the database, but the pet no longer shows anywhere
        ActivityLog::record('deleted', 'Pets', 'Removed pet ' . $pet->name . ' (added by mistake).', $pet);

        return redirect()->route('portal.pets.index')->with('status', $pet->name . ' was removed from your pets.');
    }

    // ---------- helpers ----------

    // Anything the clinic recorded for this pet
    private function hasClinicHistory(Pet $pet): bool
    {
        return $pet->appointments()->exists()
            || $pet->visits()->exists()
            || $pet->medicalRecords()->exists()
            || $pet->vaccinations()->exists()
            || $pet->careInstructions()->exists()
            || Transaction::where('pet_id', $pet->id)->exists()
            || Waiver::where('pet_id', $pet->id)->exists();
    }

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
            'age_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'color' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'petname' => 'pet name',
            'sex' => 'sex',
            'age' => 'age (years)',
            'age_months' => 'age (months)',
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
            'birthdate' => Pet::birthdateFromAge((int) $data['age'], (int) ($data['age_months'] ?? 0)),
            'color' => $data['color'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
