<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CareInstruction;
use App\Models\MedicalRecord;
use App\Models\Pet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Digital pet records for the Veterinarian/Admin (capstone FR-REQ007 - FR-REQ010).
 * The vet TYPES the assessment/diagnosis; nothing is automated (decision P7).
 * Staff only see vaccination history (P4); Super Admin never sees medical notes (P1).
 * Records are never deleted: they are part of the pet's medical history.
 */
class MedicalRecordController extends Controller
{
    private const MAX_ROWS = 20;   // most treatments / prescriptions / vaccines in one record

    // Pet Records page: every pet with its number of records and last visit, plus search
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $species = $request->query('species');

        $pets = Pet::with('customer')
            ->withCount('medicalRecords')
            ->withMax('medicalRecords', 'record_date')
            ->when(in_array($species, Pet::SPECIES, true), fn ($q) => $q->where('species', $species))
            ->when($search !== '', function ($query) use ($search) {
                // Each word must match the pet, the owner, or something written in a record
                foreach (preg_split('/\s+/', $search) as $word) {
                    $query->where(fn ($q) => $q
                        ->where('name', 'like', "%{$word}%")
                        ->orWhere('breed', 'like', "%{$word}%")
                        ->orWhereHas('customer', fn ($c) => $c
                            ->where('first_name', 'like', "%{$word}%")
                            ->orWhere('last_name', 'like', "%{$word}%"))
                        ->orWhereHas('medicalRecords', fn ($r) => $r
                            ->where('chief_complaint', 'like', "%{$word}%")
                            ->orWhere('diagnosis', 'like', "%{$word}%")));
                }
            })
            ->orderByDesc('medical_records_max_record_date')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.records.index', compact('pets', 'search', 'species'));
    }

    public function create(Request $request): View
    {
        $record = new MedicalRecord([
            'record_type' => 'consultation',
            'record_date' => today(),
        ]);
        $record->pet_id = Pet::whereKey($request->query('pet'))->value('id');

        return view('admin.records.form', [
            'record' => $record,
            'pets' => Pet::with('customer')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        $pet = Pet::findOrFail($data['pet_id']);

        $record = DB::transaction(function () use ($request, $data, $pet) {
            $record = new MedicalRecord($data['record']);
            $record->pet_id = $pet->id;
            $record->veterinarian_id = $request->user()->id;   // the vet who wrote it
            $record->save();

            $this->saveRows($record, $data, $request->user()->id);

            // Optional care instructions written together with the record
            if ($data['care']) {
                $care = new CareInstruction($data['care'] + ['pet_id' => $pet->id]);
                $record->careInstructions()->save($care);
                if ($request->boolean('care_release')) {
                    $this->markReleased($care, $request->user()->id);
                }
            }

            return $record;
        });

        ActivityLog::record('created', 'Pet Records', 'Added a ' . $record->record_type . ' record for ' . $pet->name . '.', $record);

        return redirect()->route('admin.records.show', $record)->with('status', 'Medical record saved.');
    }

    public function show(MedicalRecord $record): View
    {
        $record->load(['pet.customer', 'veterinarian', 'treatments', 'prescriptions', 'vaccinations',
            'careInstructions' => fn ($q) => $q->with('releasedBy')->latest()]);

        return view('admin.records.show', compact('record'));
    }

    public function edit(MedicalRecord $record): View
    {
        $record->load(['pet.customer', 'treatments', 'prescriptions', 'vaccinations']);

        return view('admin.records.form', ['record' => $record, 'pets' => collect()]);
    }

    public function update(Request $request, MedicalRecord $record): RedirectResponse
    {
        $data = $this->validated($request, false);

        DB::transaction(function () use ($request, $record, $data) {
            $record->update($data['record']);

            // The lists are saved again exactly as they are on the form
            $record->treatments()->delete();
            $record->prescriptions()->delete();
            $record->vaccinations()->delete();
            $this->saveRows($record, $data, $request->user()->id);
        });

        ActivityLog::record('updated', 'Pet Records', 'Updated the ' . $record->record_type . ' record of ' . $record->pet->name
            . ' dated ' . $record->record_date->format('M j, Y') . '.', $record);

        return redirect()->route('admin.records.show', $record)->with('status', 'Medical record updated.');
    }

    // ---------- Care instructions (shown to the owner only after release) ----------

    public function storeCare(Request $request, MedicalRecord $record): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string', 'max:5000'],
        ]);

        $care = new CareInstruction($data + ['pet_id' => $record->pet_id]);
        $record->careInstructions()->save($care);

        if ($request->boolean('release')) {
            $this->markReleased($care, $request->user()->id);
        }

        ActivityLog::record('created', 'Pet Records', 'Wrote care instructions "' . $care->title . '" for ' . $record->pet->name . '.', $care);

        return back()->with('status', $care->is_released
            ? 'Care instructions saved and released to the owner.'
            : 'Care instructions saved as a draft. The owner sees them only after you release them.');
    }

    public function releaseCare(CareInstruction $care): RedirectResponse
    {
        if ($care->is_released) {
            return back()->with('status', 'These care instructions were already released.');
        }

        $this->markReleased($care, request()->user()->id);
        ActivityLog::record('released', 'Pet Records', 'Released care instructions "' . $care->title . '" to the owner of ' . $care->pet->name . '.', $care);

        return back()->with('status', 'Care instructions released. The owner can now see and download them in the portal.');
    }

    // Only drafts can be deleted; released instructions stay because the owner already has them
    public function destroyCare(CareInstruction $care): RedirectResponse
    {
        if ($care->is_released) {
            return back()->withErrors(['care' => 'Released care instructions cannot be deleted.']);
        }

        $care->delete();
        ActivityLog::record('deleted', 'Pet Records', 'Deleted draft care instructions "' . $care->title . '".');

        return back()->with('status', 'Draft care instructions deleted.');
    }

    // ---------- Helpers ----------

    private function validated(Request $request, bool $creating): array
    {
        $max = self::MAX_ROWS;

        $data = $request->validate([
            'pet_id' => $creating ? ['required', 'integer', 'exists:pets,id'] : ['prohibited'],
            'record_type' => ['required', 'in:' . implode(',', MedicalRecord::TYPES)],
            'record_date' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.01', 'max:200'],
            'temperature_c' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'chief_complaint' => ['nullable', 'string', 'max:2000'],
            'findings' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => ['required_if:record_type,consultation,treatment', 'nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'treatments' => ['nullable', 'array', "max:{$max}"],
            'treatments.*.procedure_name' => ['nullable', 'string', 'max:255', 'required_with:treatments.*.description'],
            'treatments.*.description' => ['nullable', 'string', 'max:2000'],

            'prescriptions' => ['nullable', 'array', "max:{$max}"],
            'prescriptions.*.medicine_name' => ['nullable', 'string', 'max:255',
                'required_with:prescriptions.*.dosage,prescriptions.*.frequency,prescriptions.*.duration,prescriptions.*.instructions'],
            'prescriptions.*.dosage' => ['nullable', 'string', 'max:255', 'required_with:prescriptions.*.medicine_name'],
            'prescriptions.*.frequency' => ['nullable', 'string', 'max:255', 'required_with:prescriptions.*.medicine_name'],
            'prescriptions.*.duration' => ['nullable', 'string', 'max:255'],
            'prescriptions.*.instructions' => ['nullable', 'string', 'max:2000'],

            'vaccinations' => ['nullable', 'array', "max:{$max}"],
            'vaccinations.*.vaccine_name' => ['nullable', 'string', 'max:255',
                'required_with:vaccinations.*.batch_number,vaccinations.*.next_due_date'],
            'vaccinations.*.batch_number' => ['nullable', 'string', 'max:100'],
            'vaccinations.*.next_due_date' => ['nullable', 'date', 'after:record_date'],

            'care_title' => ['nullable', 'string', 'max:255', 'required_with:care_instructions'],
            'care_instructions' => ['nullable', 'string', 'max:5000', 'required_with:care_title'],
        ], [
            'diagnosis.required_if' => 'Please type the assessment / diagnosis for a consultation or treatment.',
            'record_date.before_or_equal' => 'The record date cannot be in the future.',
            'temperature_c.min' => 'The temperature must be between 30 and 45 °C.',
            'temperature_c.max' => 'The temperature must be between 30 and 45 °C.',
            'treatments.*.procedure_name.required_with' => 'Each treatment needs a procedure name.',
            'prescriptions.*.medicine_name.required_with' => 'Each prescription needs a medicine name.',
            'prescriptions.*.dosage.required_with' => 'Each prescription needs a dosage.',
            'prescriptions.*.frequency.required_with' => 'Each prescription needs a frequency (e.g. twice a day).',
            'vaccinations.*.vaccine_name.required_with' => 'Each vaccination needs a vaccine name.',
            'vaccinations.*.next_due_date.after' => 'The next due date must be after the record date.',
            'care_title.required_with' => 'Care instructions need a title.',
            'care_instructions.required_with' => 'Please type the care instructions.',
        ]);

        // Empty rows on the form are simply skipped
        $rows = fn (string $list, string $name) => collect($data[$list] ?? [])
            ->filter(fn ($row) => filled($row[$name] ?? null))
            ->values()
            ->all();

        $result = [
            'pet_id' => $data['pet_id'] ?? null,
            'record' => collect($data)->only(['record_type', 'record_date', 'weight_kg', 'temperature_c',
                'chief_complaint', 'findings', 'diagnosis', 'notes'])->all(),
            'treatments' => $rows('treatments', 'procedure_name'),
            'prescriptions' => $rows('prescriptions', 'medicine_name'),
            'vaccinations' => $rows('vaccinations', 'vaccine_name'),
            'care' => filled($data['care_title'] ?? null)
                ? ['title' => $data['care_title'], 'instructions' => $data['care_instructions']]
                : null,
        ];

        if ($result['record']['record_type'] === 'vaccination' && $result['vaccinations'] === []) {
            throw ValidationException::withMessages(['vaccinations' => 'A vaccination record needs at least one vaccine.']);
        }

        return $result;
    }

    private function saveRows(MedicalRecord $record, array $data, int $vetId): void
    {
        foreach ($data['treatments'] as $row) {
            $record->treatments()->create($row);
        }

        foreach ($data['prescriptions'] as $row) {
            $record->prescriptions()->create($row);
        }

        foreach ($data['vaccinations'] as $row) {
            $vaccination = $record->vaccinations()->make([
                'pet_id' => $record->pet_id,
                'vaccine_name' => $row['vaccine_name'],
                'batch_number' => $row['batch_number'] ?? null,
                'date_given' => $record->record_date,
                'next_due_date' => $row['next_due_date'] ?? null,
            ]);
            $vaccination->given_by = $vetId;
            $vaccination->save();
        }
    }

    private function markReleased(CareInstruction $care, int $userId): void
    {
        $care->is_released = true;
        $care->released_at = now();
        $care->released_by = $userId;
        $care->save();
    }
}
