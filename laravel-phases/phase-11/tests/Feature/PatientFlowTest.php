<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\MedicalRecord;
use App\Models\PatientVisit;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 11: walk-in registration and the patient flow board.
 */
class PatientFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $vet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
    }

    private function walkIn(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Pedro', 'last_name' => 'Reyes', 'contact_number' => '09175550000',
            'email' => '', 'address' => 'Las Piñas City',
            'pet_name' => 'Choco', 'species' => 'dog', 'breed' => 'Aspin', 'gender' => 'male', 'age' => '2',
            'service_id' => Service::where('name', 'Consultation')->value('id'),
            'veterinarian_id' => '', 'notes' => 'Limping.',
        ], $overrides);
    }

    public function test_board_shows_todays_appointments_to_check_in(): void
    {
        $this->actingAs($this->staff)->get('/staff/patient-flow')
            ->assertOk()
            ->assertSee('Patient Flow')->assertSee('Waiting')->assertSee('Ongoing')->assertSee('Completed')->assertSee('Cancelled')
            ->assertSee("Today's Appointments Not Yet Checked In", false)
            ->assertSee('Max')->assertSee('Buddy')->assertDontSee('Coco')   // Coco is tomorrow
            ->assertSee('+ New Walk-in');
    }

    public function test_new_walk_in_goes_to_the_board_with_queue_numbers(): void
    {
        $this->actingAs($this->staff)->get('/staff/walk-ins/create')->assertOk()->assertSee('New Walk-in');

        $this->actingAs($this->staff)->post('/staff/walk-ins', $this->walkIn())
            ->assertRedirect('/staff/patient-flow')->assertSessionHas('status', 'Choco was added to the board as queue #1.');

        $customer = Customer::where('contact_number', '09175550000')->first();
        $this->assertTrue($customer->is_walk_in);
        $this->assertNull($customer->user_id);
        $pet = $customer->pets()->first();
        $this->assertSame('Choco', $pet->name);

        $visit = PatientVisit::first();
        $this->assertSame('waiting', $visit->status);
        $this->assertSame('walk_in', $visit->visit_type);
        $this->assertSame(1, $visit->queue_number);
        $this->assertSame($this->staff->id, $visit->handled_by);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Patient Flow', 'action' => 'created']);

        // second walk-in today gets #2
        $this->actingAs($this->staff)->post('/staff/walk-ins', $this->walkIn(['contact_number' => '09175550001', 'pet_name' => 'Mochi']));
        $this->assertSame(2, PatientVisit::where('pet_id', Pet::where('name', 'Mochi')->value('id'))->value('queue_number'));

        $this->actingAs($this->staff)->get('/staff/patient-flow')->assertOk()->assertSee('#1')->assertSee('Choco')->assertSee('#2')->assertSee('Mochi');
    }

    public function test_duplicate_mobile_number_shows_a_warning_first(): void
    {
        // 09181234567 belongs to John Cruz in the demo data
        $this->actingAs($this->staff)->from('/staff/walk-ins/create')
            ->post('/staff/walk-ins', $this->walkIn(['contact_number' => '09181234567']))
            ->assertRedirect('/staff/walk-ins/create')->assertSessionHas('duplicates');
        $this->assertSame(0, PatientVisit::count());

        $this->actingAs($this->staff)->withSession(['duplicates' => [Customer::where('first_name', 'John')->value('id')]])
            ->get('/staff/walk-ins/create')->assertSee('This mobile number is already registered')->assertSee('John Cruz')->assertSee('Use This Customer');

        // confirmed as a different person -> saved
        $this->actingAs($this->staff)->post('/staff/walk-ins', $this->walkIn(['contact_number' => '09181234567', 'confirm_duplicate' => '1']))
            ->assertRedirect('/staff/patient-flow');
        $this->assertSame(2, Customer::where('contact_number', '09181234567')->count());
    }

    public function test_walk_in_validation(): void
    {
        $this->actingAs($this->staff)->post('/staff/walk-ins', $this->walkIn([
            'first_name' => '', 'contact_number' => '12345', 'pet_name' => '', 'species' => 'dragon', 'age' => '-1', 'service_id' => '',
        ]))->assertSessionHasErrors(['first_name', 'contact_number', 'pet_name', 'species', 'age', 'service_id']);

        $this->assertSame(0, PatientVisit::count());
    }

    public function test_returning_customer_checks_in_existing_or_new_pet(): void
    {
        $john = Customer::where('first_name', 'John')->first();
        $buddy = Pet::where('name', 'Buddy')->first();
        $max = Pet::where('name', 'Max')->first();

        $this->actingAs($this->staff)->get("/staff/customers/{$john->id}")->assertSee('Walk-in Check-in');
        $this->actingAs($this->staff)->get("/staff/customers/{$john->id}/check-in")->assertOk()->assertSee('Buddy')->assertDontSee('Max');

        // someone else's pet is refused
        $this->actingAs($this->staff)->post("/staff/customers/{$john->id}/check-in", $this->walkIn(['pet_id' => $max->id]))
            ->assertSessionHasErrors('pet_id');

        $this->actingAs($this->staff)->post("/staff/customers/{$john->id}/check-in", $this->walkIn(['pet_id' => $buddy->id]))
            ->assertRedirect('/staff/patient-flow');
        $this->assertSame($buddy->id, PatientVisit::first()->pet_id);

        // a new pet for the same customer
        $this->actingAs($this->staff)->post("/staff/customers/{$john->id}/check-in", $this->walkIn(['pet_id' => 'new', 'pet_name' => 'Kitty', 'species' => 'cat']))
            ->assertRedirect('/staff/patient-flow');
        $this->assertSame($john->id, Pet::where('name', 'Kitty')->value('customer_id'));
        $this->assertSame(1, Customer::where('first_name', 'John')->count());   // no duplicate customer
    }

    public function test_appointment_check_in_and_status_flow(): void
    {
        $max = Appointment::whereDate('appointment_date', today())->where('status', 'pending')->first();   // Max, pending

        $this->actingAs($this->staff)->post("/staff/appointments/{$max->id}/check-in")->assertSessionHasNoErrors();
        $visit = PatientVisit::where('appointment_id', $max->id)->first();
        $this->assertSame('appointment', $visit->visit_type);
        $this->assertSame('confirmed', $max->fresh()->status);   // arriving confirms the booking

        // cannot check in twice
        $this->actingAs($this->staff)->post("/staff/appointments/{$max->id}/check-in")->assertSessionHasErrors('visit');

        // tomorrow's appointment cannot be checked in today
        $coco = Appointment::whereDate('appointment_date', today()->addDay())->first();
        $this->actingAs($this->staff)->post("/staff/appointments/{$coco->id}/check-in")->assertSessionHasErrors('visit');

        // waiting -> completed is not allowed
        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'completed'])->assertSessionHasErrors('status');

        // waiting -> ongoing (with a vet) -> completed
        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'ongoing', 'veterinarian_id' => $this->vet->id])
            ->assertSessionHasNoErrors();
        $visit->refresh();
        $this->assertSame('ongoing', $visit->status);
        $this->assertNotNull($visit->started_at);
        $this->assertSame($this->vet->id, $visit->veterinarian_id);

        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $visit->fresh()->status);
        $this->assertSame('completed', $max->fresh()->status);   // the appointment is completed too

        // completed is final
        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'x'])
            ->assertSessionHasErrors('status');
    }

    public function test_cancel_needs_a_reason(): void
    {
        $this->actingAs($this->staff)->post('/staff/walk-ins', $this->walkIn());
        $visit = PatientVisit::first();

        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'cancelled'])->assertSessionHasErrors('cancel_reason');
        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Owner left.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Owner left.', $visit->fresh()->cancel_reason);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Patient Flow', 'action' => 'cancelled']);
    }

    public function test_purpose_filter_date_and_auto_refresh_part(): void
    {
        $this->actingAs($this->staff)->post('/staff/walk-ins', $this->walkIn());   // consultation
        $this->actingAs($this->staff)->get('/staff/patient-flow');   // shows (and clears) the "added to the board" message

        $this->actingAs($this->staff)->get('/staff/patient-flow?purpose=grooming')->assertOk()->assertDontSee('Choco');
        $this->actingAs($this->staff)->get('/staff/patient-flow?purpose=consultation')->assertOk()->assertSee('Choco');

        // only the board part, without the page layout
        $this->actingAs($this->staff)->get('/staff/patient-flow?partial=1')
            ->assertOk()->assertSee('Choco')->assertDontSee('<html', false);

        // yesterday's board is empty
        $this->actingAs($this->staff)->get('/staff/patient-flow?date=' . today()->subDay()->format('Y-m-d'))
            ->assertOk()->assertDontSee('Choco')->assertSee('Back to Today');
    }

    public function test_vet_views_board_and_writes_a_record_for_the_visit(): void
    {
        $this->actingAs($this->staff)->post('/staff/walk-ins', $this->walkIn());
        $visit = PatientVisit::first();
        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'ongoing']);

        $this->actingAs($this->vet)->get('/admin/patient-flow')
            ->assertOk()->assertSee('Choco')->assertSee('Write Record')
            ->assertDontSee('Confirm Cancel')->assertDontSee('+ New Walk-in');   // the vet only views the board

        $this->actingAs($this->vet)->get("/admin/pet-records/create?pet={$visit->pet_id}&visit={$visit->id}")
            ->assertOk()->assertSee('name="patient_visit_id" value="' . $visit->id . '"', false);

        $this->actingAs($this->vet)->post('/admin/pet-records', [
            'pet_id' => $visit->pet_id, 'patient_visit_id' => $visit->id, 'record_type' => 'consultation',
            'record_date' => today()->format('Y-m-d'), 'diagnosis' => 'Sprain.',
        ])->assertSessionHasNoErrors();
        $this->assertSame($visit->id, MedicalRecord::latest('id')->first()->patient_visit_id);

        // a visit of another pet cannot be linked
        $this->actingAs($this->vet)->post('/admin/pet-records', [
            'pet_id' => Pet::where('name', 'Luna')->value('id'), 'patient_visit_id' => $visit->id, 'record_type' => 'consultation',
            'record_date' => today()->format('Y-m-d'), 'diagnosis' => 'x',
        ])->assertSessionHasErrors('patient_visit_id');
    }

    public function test_access_rules(): void
    {
        $owner = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $super = User::where('email', 'superadmin@fmhanimalclinic.com')->first();

        // the vet views but cannot change statuses or register walk-ins (decision: Staff run the front desk)
        $this->actingAs($this->vet)->get('/staff/patient-flow')->assertForbidden();
        $this->actingAs($this->vet)->post('/staff/walk-ins', $this->walkIn())->assertForbidden();

        foreach ([$owner, $super] as $user) {
            $this->actingAs($user)->get('/staff/patient-flow')->assertForbidden();
            $this->actingAs($user)->get('/admin/patient-flow')->assertForbidden();
            $this->actingAs($user)->post('/staff/walk-ins', $this->walkIn())->assertForbidden();
        }
        $this->actingAs($this->staff)->get('/admin/patient-flow')->assertForbidden();

        $this->assertSame(0, PatientVisit::count());
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/staff/patient-flow')->assertRedirect('/login');
        $this->get('/admin/patient-flow')->assertRedirect('/login');
        $this->post('/staff/walk-ins', $this->walkIn())->assertRedirect('/login');
    }
}
