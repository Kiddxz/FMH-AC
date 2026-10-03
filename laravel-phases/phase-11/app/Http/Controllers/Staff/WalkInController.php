<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\PatientFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Walk-in registration (capstone FR-REQ011): a customer who comes without an appointment.
 *  - New walk-in: new customer + new pet, then the pet goes to the patient flow board.
 *  - Returning customer: pick one of their pets (or add a new pet), then the board.
 * If the mobile number is already registered, the staff is warned first (possible duplicate).
 */
class WalkInController extends Controller
{
    // ---------- New customer ----------

    public function create(): View
    {
        return view('staff.walk-ins.create', $this->formData());
    }

    public function store(Request $request, PatientFlow $flow): RedirectResponse
    {
        $data = $request->validate($this->customerRules() + $this->petRules() + $this->visitRules(), $this->messages(), $this->names());

        // Same mobile number already registered? Show the matches before making a second record.
        $matches = Customer::where('contact_number', $data['contact_number'])->pluck('id');
        if ($matches->isNotEmpty() && ! $request->boolean('confirm_duplicate')) {
            return back()->withInput()->with('duplicates', $matches->all());
        }

        $visit = DB::transaction(function () use ($data, $request, $flow) {
            $customer = new Customer([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'contact_number' => $data['contact_number'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
            ]);
            $customer->is_walk_in = true;
            $customer->save();

            $pet = $this->newPet($customer, $data);

            return $flow->checkIn($this->visitData($customer, $pet, $data), $request->user()->id);
        });

        ActivityLog::record('created', 'Patient Flow', 'Registered walk-in ' . $visit->customer->full_name . ' with ' . $visit->pet->name
            . ' (queue #' . $visit->queue_number . ').', $visit);

        return redirect()->route('staff.flow.index')->with('status', $visit->pet->name . ' was added to the board as queue #' . $visit->queue_number . '.');
    }

    // ---------- Returning customer ----------

    public function createForCustomer(Customer $customer): View
    {
        $customer->load(['pets' => fn ($q) => $q->where('status', 'active')->orderBy('name')]);

        return view('staff.walk-ins.customer', ['customer' => $customer] + $this->formData());
    }

    public function storeForCustomer(Request $request, Customer $customer, PatientFlow $flow): RedirectResponse
    {
        $isNewPet = $request->input('pet_id') === 'new';

        $rules = $this->visitRules() + [
            'pet_id' => ['required', $isNewPet ? 'in:new' : Rule::exists('pets', 'id')
                ->where('customer_id', $customer->id)->where('status', 'active')->whereNull('deleted_at')],
        ];
        if ($isNewPet) {
            $rules += $this->petRules();
        }

        $data = $request->validate($rules, $this->messages() + ['pet_id.exists' => 'Please choose one of this customer\'s pets.'], $this->names());

        $visit = DB::transaction(function () use ($data, $customer, $isNewPet, $request, $flow) {
            $pet = $isNewPet ? $this->newPet($customer, $data) : Pet::findOrFail($data['pet_id']);

            return $flow->checkIn($this->visitData($customer, $pet, $data), $request->user()->id);
        });

        ActivityLog::record('checked_in', 'Patient Flow', 'Checked in walk-in ' . $customer->full_name . ' with ' . $visit->pet->name
            . ' (queue #' . $visit->queue_number . ').', $visit);

        return redirect()->route('staff.flow.index')->with('status', $visit->pet->name . ' was added to the board as queue #' . $visit->queue_number . '.');
    }

    // ---------- helpers ----------

    private function formData(): array
    {
        return [
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'vets' => User::whereHas('role', fn ($q) => $q->where('slug', Role::VET_ADMIN))->where('status', 'active')->orderBy('last_name')->get(),
        ];
    }

    private function customerRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact_number' => ['required', 'regex:/^09\d{9}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function petRules(): array
    {
        return [
            'pet_name' => ['required', 'string', 'max:100'],
            'species' => ['required', Rule::in(Pet::SPECIES)],
            'breed' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'min:0', 'max:50'],
        ];
    }

    private function visitRules(): array
    {
        return [
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'veterinarian_id' => ['nullable', Rule::exists('users', 'id')->where('role_id', Role::where('slug', Role::VET_ADMIN)->value('id'))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function messages(): array
    {
        return ['contact_number.regex' => 'Enter an 11-digit mobile number that starts with 09 (e.g. 09171234567).'];
    }

    private function names(): array
    {
        return ['contact_number' => 'mobile number', 'pet_name' => 'pet name', 'service_id' => 'service', 'veterinarian_id' => 'veterinarian', 'pet_id' => 'pet'];
    }

    private function newPet(Customer $customer, array $data): Pet
    {
        $pet = new Pet([
            'name' => $data['pet_name'],
            'species' => $data['species'],
            'breed' => $data['breed'] ?? null,
            'gender' => $data['gender'],
            'birthdate' => now()->subYears((int) $data['age'])->toDateString(),   // age -> approximate birthdate
        ]);
        $pet->customer_id = $customer->id;
        $pet->save();

        return $pet;
    }

    private function visitData(Customer $customer, Pet $pet, array $data): array
    {
        return [
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $data['service_id'],
            'visit_type' => 'walk_in',
            'veterinarian_id' => $data['veterinarian_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
