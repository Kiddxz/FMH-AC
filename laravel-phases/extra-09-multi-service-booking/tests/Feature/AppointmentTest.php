<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 9: time slots, online booking, no double booking, status rules, history and download.
 */
class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private User $mark;
    private User $staff;
    private Pet $max;
    private Service $consult;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 07:00:00');   // a Monday, before opening time
        $this->seed(DatabaseSeeder::class);
        $this->mark = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->max = Pet::where('name', 'Max')->first();
        $this->consult = Service::where('name', 'Consultation')->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function book(User $user, array $overrides = [])
    {
        return $this->actingAs($user)->post('/portal/appointments', array_merge([
            'pet_id' => $this->max->id, 'service_id' => $this->consult->id,
            'appointment_date' => '2026-10-06', 'appointment_time' => '09:00', 'reason' => 'Check-up',
        ], $overrides));
    }

    public function test_slots_follow_clinic_hours(): void
    {
        $tuesday = $this->actingAs($this->mark)->getJson('/appointments/slots?date=2026-10-06')->assertOk()->json();
        $this->assertTrue($tuesday['open']);
        $this->assertCount(18, $tuesday['slots']);                         // 8:00 ... 16:30 every 30 minutes
        $this->assertSame('08:00', $tuesday['slots'][0]['time']);
        $this->assertSame('4:30 PM', end($tuesday['slots'])['label']);

        $sunday = $this->actingAs($this->mark)->getJson('/appointments/slots?date=2026-10-11')->json();
        $this->assertFalse($sunday['open']);
    }

    public function test_slots_need_a_login(): void
    {
        $this->getJson('/appointments/slots?date=2026-10-06')->assertUnauthorized();
    }

    public function test_customer_books_own_pet_as_pending(): void
    {
        $response = $this->book($this->mark);
        $appointment = Appointment::latest('id')->first();

        $response->assertRedirect("/portal/appointments/{$appointment->id}");
        $this->assertSame('pending', $appointment->status);
        $this->assertSame($this->mark->customer->id, $appointment->customer_id);
        $this->assertSame('APP-' . str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT), $appointment->reference);
        $this->assertSame('09:00:00', $appointment->appointment_time);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Appointments', 'action' => 'created']);
    }

    public function test_customer_cannot_book_someone_elses_pet_or_inactive_service(): void
    {
        $buddy = Pet::where('name', 'Buddy')->first();
        $this->book($this->mark, ['pet_id' => $buddy->id])->assertSessionHasErrors('pet_id');

        $nails = Service::where('name', 'Nail Trimming')->first();   // inactive
        $this->book($this->mark, ['service_id' => $nails->id])->assertSessionHasErrors('service_ids.0');
    }

    public function test_full_slot_and_same_pet_double_booking_are_rejected(): void
    {
        $this->book($this->mark)->assertSessionHasNoErrors();

        // same pet, same time again
        $this->book($this->mark)->assertSessionHasErrors(['appointment_time' => 'This pet already has an appointment at that date and time.']);

        // fill the slot (max 3 per slot) with other pets
        foreach (['Luna', 'Buddy'] as $name) {
            $pet = Pet::where('name', $name)->first();
            $this->actingAs($this->staff)->post('/staff/appointments', [
                'pet_id' => $pet->id, 'service_id' => $this->consult->id, 'appointment_date' => '2026-10-06', 'appointment_time' => '09:00',
            ])->assertSessionHasNoErrors();
        }
        $coco = Pet::where('name', 'Coco')->first();
        $this->actingAs($this->staff)->post('/staff/appointments', [
            'pet_id' => $coco->id, 'service_id' => $this->consult->id, 'appointment_date' => '2026-10-06', 'appointment_time' => '09:00',
        ])->assertSessionHasErrors(['appointment_time' => 'This time slot is already full. Please choose another time.']);

        $slot = collect($this->actingAs($this->mark)->getJson('/appointments/slots?date=2026-10-06')->json('slots'))->firstWhere('time', '09:00');
        $this->assertFalse($slot['available']);
        $this->assertSame(0, $slot['remaining']);
    }

    public function test_cancelled_appointments_free_the_slot(): void
    {
        $this->book($this->mark);
        $appointment = Appointment::latest('id')->first();
        $this->actingAs($this->mark)->patch("/portal/appointments/{$appointment->id}/cancel", ['cancel_reason' => 'Conflict']);

        $this->book($this->mark)->assertSessionHasNoErrors();   // same pet, same slot works again
    }

    public function test_invalid_dates_and_times(): void
    {
        $this->book($this->mark, ['appointment_date' => '2026-10-04'])->assertSessionHasErrors('appointment_date');   // yesterday
        $this->book($this->mark, ['appointment_date' => '2026-10-11'])->assertSessionHasErrors('appointment_date');   // Sunday
        $this->book($this->mark, ['appointment_date' => '2027-03-01'])->assertSessionHasErrors('appointment_date');   // > 3 months
        $this->book($this->mark, ['appointment_time' => '09:15'])->assertSessionHasErrors('appointment_time');        // not a slot
        $this->book($this->mark, ['appointment_time' => '17:00'])->assertSessionHasErrors('appointment_time');        // closed

        Carbon::setTestNow('2026-10-06 10:05:00');
        $this->book($this->mark, ['appointment_time' => '10:00'])->assertSessionHasErrors('appointment_time');        // already passed
        $this->book($this->mark, ['appointment_time' => '10:30'])->assertSessionHasNoErrors();                        // later today is fine
    }

    public function test_history_download_and_ownership(): void
    {
        $this->book($this->mark);
        $mine = Appointment::latest('id')->first();

        $this->actingAs($this->mark)->get('/portal/appointments')
            ->assertOk()->assertSee('Upcoming')->assertSee($mine->reference)->assertSee('Past and Cancelled');

        $csv = $this->actingAs($this->mark)->get('/portal/appointments/download');
        $csv->assertOk();
        $this->assertStringContainsString($mine->reference, $csv->streamedContent());
        $this->assertStringNotContainsString('Buddy', $csv->streamedContent());

        $johns = Appointment::whereHas('pet', fn ($q) => $q->where('name', 'Buddy'))->first();
        $this->actingAs($this->mark)->get("/portal/appointments/{$johns->id}")->assertForbidden();
        $this->actingAs($this->mark)->patch("/portal/appointments/{$johns->id}/cancel", ['cancel_reason' => 'x'])->assertForbidden();
    }

    public function test_customer_cancel_rules(): void
    {
        $this->book($this->mark);
        $appointment = Appointment::latest('id')->first();

        $this->actingAs($this->mark)->patch("/portal/appointments/{$appointment->id}/cancel", [])->assertSessionHasErrors('cancel_reason');
        $this->actingAs($this->mark)->patch("/portal/appointments/{$appointment->id}/cancel", ['cancel_reason' => 'Pet is better'])
            ->assertRedirect('/portal/appointments');
        $this->assertSame('cancelled', $appointment->fresh()->status);

        $completed = Appointment::where('pet_id', $this->max->id)->where('status', 'completed')->first();
        $this->actingAs($this->mark)->patch("/portal/appointments/{$completed->id}/cancel", ['cancel_reason' => 'x'])->assertForbidden();
    }

    public function test_status_rules_for_the_clinic(): void
    {
        $this->book($this->mark);
        $appointment = Appointment::latest('id')->first();

        // pending cannot be completed directly
        $this->actingAs($this->staff)->patch("/staff/appointments/{$appointment->id}/complete")->assertSessionHasErrors('status');
        $this->assertSame('pending', $appointment->fresh()->status);

        $this->actingAs($this->staff)->patch("/staff/appointments/{$appointment->id}/confirm");
        $this->assertSame('confirmed', $appointment->fresh()->status);

        $this->actingAs($this->staff)->patch("/staff/appointments/{$appointment->id}/complete");
        $this->assertSame('completed', $appointment->fresh()->status);

        // completed is final
        $this->actingAs($this->staff)->patch("/staff/appointments/{$appointment->id}/cancel", ['cancel_reason' => 'x'])->assertSessionHasErrors('status');
        $this->actingAs($this->staff)->get("/staff/appointments/{$appointment->id}/edit")->assertRedirect("/staff/appointments/{$appointment->id}");
        $this->assertDatabaseHas('activity_logs', ['action' => 'completed', 'module' => 'Appointments']);
    }

    public function test_clinic_cancel_needs_a_reason(): void
    {
        $appointment = Appointment::where('status', 'pending')->first();
        $this->actingAs($this->staff)->patch("/staff/appointments/{$appointment->id}/cancel", [])->assertSessionHasErrors('cancel_reason');
        $this->actingAs($this->staff)->patch("/staff/appointments/{$appointment->id}/cancel", ['cancel_reason' => 'Vet unavailable']);
        $this->assertSame('Vet unavailable', $appointment->fresh()->cancel_reason);
    }

    public function test_clinic_creates_confirmed_appointment_and_reschedules(): void
    {
        $vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $bella = Pet::where('name', 'Bella')->first();

        $this->actingAs($this->staff)->post('/staff/appointments', [
            'pet_id' => $bella->id, 'service_id' => $this->consult->id, 'veterinarian_id' => $vet->id,
            'appointment_date' => '2026-10-07', 'appointment_time' => '13:00', 'reason' => 'Phone booking',
        ])->assertSessionHasNoErrors();

        $appointment = Appointment::latest('id')->first();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame(Customer::where('first_name', 'Maria')->value('id'), $appointment->customer_id);

        $this->actingAs($vet)->put("/admin/appointments/{$appointment->id}", [
            'service_id' => $this->consult->id, 'veterinarian_id' => $vet->id,
            'appointment_date' => '2026-10-08', 'appointment_time' => '14:30', 'reason' => 'Phone booking', 'notes' => 'Moved',
        ])->assertRedirect("/admin/appointments/{$appointment->id}");

        $appointment->refresh();
        $this->assertSame('2026-10-08', $appointment->appointment_date->toDateString());
        $this->assertSame('14:30:00', $appointment->appointment_time);
        $this->assertDatabaseHas('activity_logs', ['action' => 'rescheduled']);

        // keeping the same slot while editing is allowed (its own place is not counted)
        $this->actingAs($vet)->put("/admin/appointments/{$appointment->id}", [
            'service_id' => $this->consult->id, 'appointment_date' => '2026-10-08', 'appointment_time' => '14:30',
        ])->assertSessionHasNoErrors();
    }

    public function test_clinic_pages_show_real_appointments(): void
    {
        $vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $this->actingAs($this->staff)->get('/staff/appointments')->assertOk()->assertSee('APP-000001')->assertSee('Max');
        $this->actingAs($this->staff)->get('/staff/appointments?status=cancelled')->assertOk()->assertSee('Rocky')->assertDontSee('Buddy');
        $this->actingAs($vet)->get('/admin/appointments/1')->assertOk()->assertSee('APP-000001')->assertSee('Mark Santos');
        $this->actingAs($vet)->get('/admin/appointments/create')->assertOk();
    }

    public function test_customers_and_super_admin_cannot_manage_clinic_appointments(): void
    {
        $super = User::where('email', 'superadmin@fmhanimalclinic.com')->first();
        $this->actingAs($this->mark)->patch('/staff/appointments/1/confirm')->assertForbidden();
        $this->actingAs($super)->patch('/admin/appointments/1/confirm')->assertForbidden();
    }
}
