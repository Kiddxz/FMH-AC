<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Pet list and pet profile for the Veterinarian/Admin.
 * Writing consultations, treatments and prescriptions comes in Phase 10 (Pet records).
 */
class PetController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $species = $request->query('species');
        $gender = $request->query('gender');

        $pets = Pet::with('customer')
            ->when(in_array($species, Pet::SPECIES, true), fn ($q) => $q->where('species', $species))
            ->when(in_array($gender, ['male', 'female'], true), fn ($q) => $q->where('gender', $gender))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('breed', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%"))))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pets.index', compact('pets', 'search', 'species', 'gender'));
    }

    public function show(Pet $pet): View
    {
        Gate::authorize('view', $pet);

        $pet->load([
            'customer',
            'vaccinations' => fn ($q) => $q->orderByDesc('date_given'),
            'medicalRecords' => fn ($q) => $q->with('veterinarian')->orderByDesc('record_date'),
            'appointments' => fn ($q) => $q->with(['service', 'veterinarian'])->orderByDesc('appointment_date'),
        ]);

        return view('admin.pets.show', compact('pet'));
    }
}
