<?php

namespace Tests\Feature;

use App\Models\CareInstruction;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 10: pet records (consultation, treatments, prescriptions, vaccinations, care instructions).
 */
class PetRecordTest extends TestCase
{
    use RefreshDatabase;

    private User $vet;
    private Pet $max;
    private Pet $buddy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $this->max = Pet::where('name', 'Max')->first();
        $this->buddy = Pet::where('name', 'Buddy')->first();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->first();
    }

    private function consultation(array $overrides = []): array
    {
        return array_merge([
            'pet_id' => $this->max->id,
            'record_type' => 'consultation',
            'record_date' => today()->format('Y-m-d'),
            'weight_kg' => '29.10',
            'temperature_c' => '39.4',
            'chief_complaint' => 'Vomiting for two days.',
            'findings' => 'Mild dehydration, tender abdomen.',
            'diagnosis' => 'Acute gastroenteritis.',
            'notes' => 'Internal: monitor kidney values.',
            'treatments' => [
                ['procedure_name' => 'IV fluids', 'description' => '500 ml LRS'],
                ['procedure_name' => '', 'description' => ''],   // empty row is skipped
            ],
            'prescriptions' => [
                ['medicine_name' => 'Metronidazole 250mg', 'dosage' => '1 tablet', 'frequency' => 'Twice a day', 'duration' => '5 days', 'instructions' => 'After meals'],
            ],
            'vaccinations' => [['vaccine_name' => '', 'batch_number' => '', 'next_due_date' => '']],
            'care_title' => 'Home care for upset stomach',
            'care_instructions' => 'Bland food for 3 days. Fresh water always.',
        ], $overrides);
    }

    public function test_records_page_lists_pets_and_searches_records(): void
    {
        $this->actingAs($this->vet)->get('/admin/pet-records')
            ->assertOk()
            ->assertSee('Max')->assertSee('1 record')->assertSee('Buddy')->assertSee('0 records')
            ->assertSee('+ Add Pet Record')
            ->assertDontSee('4 records')->assertDontSee('August 12, 2026');   // the old sample rows are gone

        // search finds a pet by a word in its diagnosis
        $this->actingAs($this->vet)->get('/admin/pet-records?search=vaccination')
            ->assertOk()->assertSee('Max')->assertDontSee('Coco');

        $this->actingAs($this->vet)->get('/admin/pet-records?species=cat')
            ->assertOk()->assertSee('Luna')->assertDontSee('Buddy');
    }

    public function test_vet_writes_a_full_consultation_with_care_instructions(): void
    {
        $this->actingAs($this->vet)->get('/admin/pet-records/create?pet=' . $this->max->id)
            ->assertOk()->assertSee('Add Pet Record')->assertSee('Assessment / Diagnosis');

        $response = $this->actingAs($this->vet)->post('/admin/pet-records', $this->consultation(['care_release' => '1']));

        $record = MedicalRecord::latest('id')->first();
        $response->assertRedirect('/admin/pet-records/' . $record->id);

        $this->assertSame($this->vet->id, $record->veterinarian_id);
        $this->assertSame('Acute gastroenteritis.', $record->diagnosis);
        $this->assertSame(1, $record->treatments()->count());
        $this->assertSame('Twice a day', $record->prescriptions()->first()->frequency);
        $this->assertSame(0, $record->vaccinations()->count());

        $care = $record->careInstructions()->first();
        $this->assertTrue($care->is_released);
        $this->assertSame($this->vet->id, $care->released_by);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Pet Records', 'action' => 'created']);

        $this->actingAs($this->vet)->get('/admin/pet-records/' . $record->id)
            ->assertOk()->assertSee('Acute gastroenteritis.')->assertSee('IV fluids')
            ->assertSee('Metronidazole 250mg')->assertSee('Released');
    }

    public function test_vaccination_record_adds_to_vaccination_history(): void
    {
        $this->actingAs($this->vet)->post('/admin/pet-records', $this->consultation([
            'pet_id' => $this->buddy->id,
            'record_type' => 'vaccination',
            'diagnosis' => '',
            'vaccinations' => [['vaccine_name' => '5-in-1 (DHPPi+L)', 'batch_number' => 'B-2026-77', 'next_due_date' => today()->addYear()->format('Y-m-d')]],
            'care_title' => '', 'care_instructions' => '',
        ]))->assertSessionHasNoErrors();

        $vaccination = $this->buddy->vaccinations()->first();
        $this->assertSame('5-in-1 (DHPPi+L)', $vaccination->vaccine_name);
        $this->assertSame(today()->format('Y-m-d'), $vaccination->date_given->format('Y-m-d'));
        $this->assertSame($this->vet->id, $vaccination->given_by);

        // Staff see it in the vaccination history (decision P4)
        $this->actingAs($this->user('assistant@fmhanimalclinic.com'))->get('/staff/pets/' . $this->buddy->id)
            ->assertOk()->assertSee('5-in-1 (DHPPi+L)');
    }

    public function test_validation_rules(): void
    {
        $this->actingAs($this->vet)->from('/admin/pet-records/create')->post('/admin/pet-records', $this->consultation([
            'diagnosis' => '',
            'record_date' => today()->addDay()->format('Y-m-d'),
            'temperature_c' => '55',
            'prescriptions' => [['medicine_name' => 'Amoxicillin', 'dosage' => '', 'frequency' => '']],
            'treatments' => [['procedure_name' => '', 'description' => 'details without a name']],
            'care_title' => 'Only a title', 'care_instructions' => '',
        ]))->assertRedirect('/admin/pet-records/create')
            ->assertSessionHasErrors([
                'diagnosis', 'record_date', 'temperature_c',
                'prescriptions.0.dosage', 'prescriptions.0.frequency',
                'treatments.0.procedure_name', 'care_instructions',
            ]);

        // a vaccination record needs at least one vaccine
        $this->actingAs($this->vet)->post('/admin/pet-records', $this->consultation(['record_type' => 'vaccination']))
            ->assertSessionHasErrors('vaccinations');

        $this->assertSame(1, MedicalRecord::count());   // only the demo record
    }

    public function test_vet_edits_a_record_and_lists_are_replaced(): void
    {
        $this->actingAs($this->vet)->post('/admin/pet-records', $this->consultation());
        $record = MedicalRecord::latest('id')->first();

        $this->actingAs($this->vet)->get('/admin/pet-records/' . $record->id . '/edit')
            ->assertOk()->assertSee('Edit Pet Record')->assertSee('IV fluids');

        $this->actingAs($this->vet)->put('/admin/pet-records/' . $record->id, $this->consultation([
            'pet_id' => null,
            'diagnosis' => 'Gastroenteritis, improving.',
            'treatments' => [['procedure_name' => 'Anti-emetic injection', 'description' => '']],
            'prescriptions' => [],
        ]))->assertRedirect('/admin/pet-records/' . $record->id);

        $record->refresh();
        $this->assertSame('Gastroenteritis, improving.', $record->diagnosis);
        $this->assertSame(['Anti-emetic injection'], $record->treatments()->pluck('procedure_name')->all());
        $this->assertSame(0, $record->prescriptions()->count());
        $this->assertSame($this->max->id, $record->pet_id);   // the pet cannot be changed
        $this->assertDatabaseHas('activity_logs', ['module' => 'Pet Records', 'action' => 'updated']);
    }

    public function test_care_instructions_draft_release_and_customer_download(): void
    {
        $owner = $this->user('owner@fmhanimalclinic.com');
        $this->actingAs($this->vet)->post('/admin/pet-records', $this->consultation());
        $record = MedicalRecord::latest('id')->first();
        $draft = $record->careInstructions()->first();
        $this->assertFalse($draft->is_released);

        // the owner cannot see or download a draft
        $this->actingAs($owner)->get('/portal/pets/' . $this->max->id)->assertOk()->assertDontSee('Home care for upset stomach');
        $this->actingAs($owner)->get('/portal/care-instructions/' . $draft->id . '/download')->assertNotFound();

        // release it
        $this->actingAs($this->vet)->patch('/admin/care-instructions/' . $draft->id . '/release')->assertRedirect();
        $this->assertTrue($draft->fresh()->is_released);

        $this->actingAs($owner)->get('/portal/pets/' . $this->max->id)
            ->assertOk()->assertSee('Home care for upset stomach')->assertSee('Metronidazole 250mg')->assertSee('Download')
            ->assertDontSee('Internal: monitor kidney values.')   // medical notes stay inside the clinic
            ->assertDontSee('Acute gastroenteritis.');

        $download = $this->actingAs($owner)->get('/portal/care-instructions/' . $draft->id . '/download');
        $download->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="care-instructions-max-', $download->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Bland food for 3 days.', $download->getContent());
        $this->assertStringContainsString('Metronidazole 250mg: 1 tablet, Twice a day, for 5 days', $download->getContent());

        // released instructions cannot be deleted
        $this->actingAs($this->vet)->delete('/admin/care-instructions/' . $draft->id)->assertSessionHasErrors('care');
        $this->assertNotNull($draft->fresh());

        // another owner cannot download it
        $john = User::factory()->create(['email' => 'other@example.com']);
        $john->role_id = $owner->role_id;
        $john->save();
        $john->customer()->create(['first_name' => 'Other', 'last_name' => 'Owner', 'contact_number' => '09170000001']);
        $this->actingAs($john->fresh())->get('/portal/care-instructions/' . $draft->id . '/download')->assertNotFound();
    }

    public function test_care_instructions_added_later_and_draft_deleted(): void
    {
        $record = MedicalRecord::first();   // demo record of Max

        $this->actingAs($this->vet)->post('/admin/pet-records/' . $record->id . '/care', [
            'title' => 'Draft note', 'instructions' => 'Not final yet.',
        ])->assertSessionHasNoErrors();
        $draft = CareInstruction::where('title', 'Draft note')->first();
        $this->assertFalse($draft->is_released);

        $this->actingAs($this->vet)->delete('/admin/care-instructions/' . $draft->id)->assertSessionHasNoErrors();
        $this->assertNull($draft->fresh());

        $this->actingAs($this->vet)->post('/admin/pet-records/' . $record->id . '/care', [
            'title' => 'Released now', 'instructions' => 'Rest for a day.', 'release' => '1',
        ]);
        $this->assertTrue(CareInstruction::where('title', 'Released now')->first()->is_released);
    }

    public function test_only_the_vet_can_reach_records(): void
    {
        $record = MedicalRecord::first();
        $care = CareInstruction::first();

        foreach (['assistant@fmhanimalclinic.com', 'superadmin@fmhanimalclinic.com', 'owner@fmhanimalclinic.com'] as $email) {
            $user = $this->user($email);
            $this->actingAs($user)->get('/admin/pet-records')->assertForbidden();
            $this->actingAs($user)->get('/admin/pet-records/' . $record->id)->assertForbidden();
            $this->actingAs($user)->post('/admin/pet-records', $this->consultation())->assertForbidden();
            $this->actingAs($user)->put('/admin/pet-records/' . $record->id, $this->consultation())->assertForbidden();
            $this->actingAs($user)->patch('/admin/care-instructions/' . $care->id . '/release')->assertForbidden();
        }

        // Staff see the vaccination history only, not the diagnosis (P4)
        $this->actingAs($this->user('assistant@fmhanimalclinic.com'))->get('/staff/pets/' . $this->max->id)
            ->assertOk()->assertSee('Rabies Vaccine')->assertDontSee('Healthy - for vaccination.');

        // Super Admin sees counts only, no medical notes (P1)
        $this->actingAs($this->user('superadmin@fmhanimalclinic.com'))->get('/superadmin/pet-records')
            ->assertOk()->assertDontSee('Healthy - for vaccination.')->assertDontSee('No reaction observed');

        // Staff and super admin cannot download care instructions from the portal
        $this->actingAs($this->user('assistant@fmhanimalclinic.com'))->get('/portal/care-instructions/' . $care->id . '/download')->assertForbidden();
    }
}
