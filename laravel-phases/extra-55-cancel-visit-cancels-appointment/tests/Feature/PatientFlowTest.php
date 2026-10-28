<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\MedicalRecord;
use App\Models\PatientVisit;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use App\Services\PatientFlow;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 11: the patient flow board. Walk-ins come from the walk-in website (GuestBookingTest).
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

    // Like assertSee, but spaces and line breaks do not matter
    // (VS Code may split a long line such as "Use This Customer" into two lines when saving).
    private function assertSeeWords(TestResponse $response, string ...$texts): TestResponse
    {
        $html = preg_replace('/\s+/', ' ', $response->getContent());
        foreach ($texts as $text) {
            $this->assertStringContainsString($text, $html, "The page does not show: {$text}");
        }

        return $response;
    }

    // A walk-in on the board, as the walk-in website puts it there (the staff no longer register walk-ins)
    private function walkIn(string $petName = 'Choco', string $service = 'Consultation'): PatientVisit
    {
        $customer = new Customer(['first_name' => 'Pedro', 'last_name' => 'Reyes', 'contact_number' => '09175550000']);
        $customer->is_walk_in = true;
        $customer->save();
        $pet = new Pet(['name' => $petName, 'species' => 'dog', 'gender' => 'male', 'birthdate' => now()->subYears(2)->toDateString()]);
        $pet->customer_id = $customer->id;
        $pet->save();

        return app(PatientFlow::class)->checkIn([
            'customer_id' => $customer->id, 'pet_id' => $pet->id, 'visit_type' => 'walk_in',
            'service_id' => Service::where('name', $service)->value('id'), 'notes' => 'Limping.',
        ], null);
    }

    public function test_board_shows_todays_appointments_to_check_in(): void
    {
        $this->actingAs($this->staff)->get('/staff/patient-flow')
            ->assertOk()
            ->assertSee('Patient Flow')->assertSee('Waiting')->assertSee('Ongoing')->assertSee('Completed')->assertSee('Cancelled')
            ->assertSee("Today's Appointments Not Yet Checked In", false)
            ->assertSee('Max')->assertSee('Buddy')->assertDontSee('Coco')   // Coco is tomorrow
            ->assertDontSee('New Walk-in');   // walk-ins book on the walk-in website now
    }

    public function test_walk_ins_are_on_the_board_with_queue_numbers(): void
    {
        $this->walkIn('Choco');
        $this->walkIn('Mochi');
        $this->assertSame(2, PatientVisit::where('pet_id', Pet::where('name', 'Mochi')->value('id'))->value('queue_number'));

        $this->actingAs($this->staff)->get('/staff/patient-flow')->assertOk()->assertSee('#1')->assertSee('Choco')->assertSee('#2')->assertSee('Mochi');
    }

    public function test_the_staff_walk_in_pages_are_gone(): void
    {
        $john = Customer::where('first_name', 'John')->first();

        $this->actingAs($this->staff)->get('/staff/walk-ins/create')->assertNotFound();
        $this->actingAs($this->staff)->post('/staff/walk-ins', [])->assertNotFound();
        $this->actingAs($this->staff)->get("/staff/customers/{$john->id}/check-in")->assertNotFound();
        $this->actingAs($this->staff)->get("/staff/customers/{$john->id}")->assertOk()->assertDontSee('Walk-in Check-in');
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
        $visit = $this->walkIn();

        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'cancelled'])->assertSessionHasErrors('cancel_reason');
        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Owner left.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Owner left.', $visit->fresh()->cancel_reason);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Patient Flow', 'action' => 'cancelled']);
    }

    public function test_cancelling_a_checked_in_appointment_also_cancels_the_appointment(): void
    {
        $max = Appointment::whereDate('appointment_date', today())->where('status', 'pending')->first();
        $this->actingAs($this->staff)->post("/staff/appointments/{$max->id}/check-in")->assertSessionHasNoErrors();
        $visit = PatientVisit::where('appointment_id', $max->id)->first();

        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Owner did not wait.'])
            ->assertSessionHasNoErrors();

        $max->refresh();
        $this->assertSame('cancelled', $max->status);
        $this->assertSame('Owner did not wait.', $max->cancel_reason);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Appointments', 'action' => 'cancelled', 'subject_id' => $max->id]);

        // the pet owner sees it as cancelled in the portal
        $this->actingAs($max->customer->user)->get('/portal/appointments')->assertOk()
            ->assertViewHas('upcoming', fn ($list) => $list->doesntContain(fn ($a) => $a->is($max)));
    }

    public function test_purpose_filter_date_and_auto_refresh_part(): void
    {
        $this->walkIn();   // consultation

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
        $visit = $this->walkIn();
        $this->actingAs($this->staff)->patch("/staff/visits/{$visit->id}/status", ['status' => 'ongoing']);

        $board = $this->actingAs($this->vet)->get('/admin/patient-flow')->assertOk();
        $this->assertSeeWords($board, 'Choco', 'Write Record');
        $board->assertDontSee('Confirm Cancel')->assertDontSee('New Walk-in');   // the vet only views the board

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

        // the vet views but cannot change statuses (decision: Staff run the front desk)
        $this->actingAs($this->vet)->get('/staff/patient-flow')->assertForbidden();

        foreach ([$owner, $super] as $user) {
            $this->actingAs($user)->get('/staff/patient-flow')->assertForbidden();
            $this->actingAs($user)->get('/admin/patient-flow')->assertForbidden();
        }
        $this->actingAs($this->staff)->get('/admin/patient-flow')->assertForbidden();

        $this->assertSame(0, PatientVisit::count());
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/staff/patient-flow')->assertRedirect('/login');
        $this->get('/admin/patient-flow')->assertRedirect('/login');
    }
}
