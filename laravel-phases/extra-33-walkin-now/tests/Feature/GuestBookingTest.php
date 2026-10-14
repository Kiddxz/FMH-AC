<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\PatientVisit;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Walk-in booking without an account: no date or time to choose. The moment of booking is saved,
 * and the pet goes straight to the Patient Flow board (Waiting) with a queue number.
 */
class GuestBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 09:30:00');   // a Monday, the clinic is open
        $this->seed(DatabaseSeeder::class);
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function form(array $changes = []): array
    {
        return $changes + [
            'first_name' => 'Rosa', 'last_name' => 'Dizon', 'contact_number' => '09181112222', 'email' => '',
            'pet_name' => 'Bantay', 'species' => 'dog', 'breed' => 'Aspin', 'gender' => 'male', 'age' => 3,
            'service_ids' => [Service::where('name', 'Consultation')->value('id'), Service::where('name', 'Deworming')->value('id')],
            'reason' => 'Check-up', 'privacy' => '1',
        ];
    }

    public function test_a_walk_in_books_and_goes_straight_to_the_queue(): void
    {
        $this->get('/book/home')->assertOk()->assertSee('Book a Visit')->assertSee('Check My Booking')->assertSee('Open now');
        $this->get('/book')->assertOk()->assertSee('Book Walk-in Visit')->assertSee('name="service_ids[]"', false)
            ->assertDontSee('name="appointment_date"', false)->assertDontSee('name="appointment_time"', false);

        $this->post('/book', $this->form())->assertRedirect('/book/done');

        // the date and time are the moment of booking; the owner is at the clinic, so it is confirmed
        $appointment = Appointment::latest('id')->first();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame('2026-10-05', $appointment->appointment_date->toDateString());
        $this->assertSame('09:30:00', $appointment->appointment_time);
        $this->assertSame('Consultation, Deworming', $appointment->service_names);

        // straight to the Patient Flow board, Waiting, queue #1
        $visit = PatientVisit::where('appointment_id', $appointment->id)->first();
        $this->assertSame('waiting', $visit->status);
        $this->assertSame('walk_in', $visit->visit_type);
        $this->assertSame(1, $visit->queue_number);

        // saved in the database like a walk-in: customer (no login) + pet
        $customer = $appointment->customer;
        $this->assertSame('Rosa Dizon', $customer->full_name);
        $this->assertTrue($customer->is_walk_in);
        $this->assertNull($customer->user_id);

        $this->get('/book/done')->assertOk()->assertSee('#1')->assertSee($appointment->reference)->assertSee('₱850.00');

        // the clinic sees it right away on the board
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->actingAs($staff)->get('/staff/patient-flow')->assertOk()->assertSee('Bantay')->assertDontSee('New Walk-in');
        $this->get('/staff/customers?search=Dizon')->assertSee('Walk-in');
    }

    public function test_the_same_owner_booking_again_keeps_one_record_and_gets_the_next_number(): void
    {
        $this->post('/book', $this->form());
        $this->post('/book', $this->form(['first_name' => 'ROSA']))->assertSessionHasErrors(['pet_name' => 'Bantay is already in today\'s queue at the clinic.']);
        $this->post('/book', $this->form(['first_name' => 'ROSA', 'pet_name' => 'Mochi', 'species' => 'cat']))->assertRedirect('/book/done');

        $this->assertSame(1, Customer::where('contact_number', '09181112222')->count());
        $this->assertSame(2, Appointment::whereHas('customer', fn ($q) => $q->where('contact_number', '09181112222'))->count());
        $this->assertSame([1, 2], PatientVisit::orderBy('queue_number')->pluck('queue_number')->all());
    }

    public function test_walk_in_booking_is_only_while_the_clinic_is_open(): void
    {
        Carbon::setTestNow('2026-10-05 07:00:00');   // before opening time

        $this->get('/book')->assertOk()->assertSee('The clinic is closed right now');
        $this->post('/book', $this->form())->assertSessionHasErrors('visit');
        $this->assertSame(0, Customer::where('contact_number', '09181112222')->count());
        $this->get('/book/home')->assertSee('Closed now');
    }

    public function test_the_form_is_checked(): void
    {
        $this->post('/book', $this->form(['contact_number' => '0818abc']))->assertSessionHasErrors('contact_number');
        $this->post('/book', $this->form(['privacy' => null]))->assertSessionHasErrors('privacy');
        $this->post('/book', $this->form(['service_ids' => []]))->assertSessionHasErrors('service_ids');
        $this->post('/book', $this->form(['email' => 'rosa@mailinator.com']))->assertSessionHasErrors('email');
        $this->post('/book', $this->form(['website' => 'http://spam.example']))->assertStatus(422);   // robot
        $this->assertSame(0, Customer::where('contact_number', '09181112222')->count());

        // a registered owner must log in instead (so the booking appears in their account)
        $mark = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $this->post('/book', $this->form(['contact_number' => $mark->customer->contact_number, 'last_name' => $mark->customer->last_name]))
            ->assertSessionHasErrors('contact_number');
    }

    public function test_the_owner_checks_and_cancels_while_still_waiting(): void
    {
        $this->post('/book', $this->form());
        $appointment = Appointment::latest('id')->first();

        $this->post('/book/check', ['reference' => $appointment->reference, 'contact_number' => '09990000000'])
            ->assertSessionHasErrors(['reference' => 'No booking matches this reference number and mobile number.']);
        $this->get('/book/my-booking')->assertRedirect('/book/check');

        $this->post('/book/check', ['reference' => strtolower($appointment->reference), 'contact_number' => '09181112222'])
            ->assertRedirect('/book/my-booking');
        $this->get('/book/my-booking')->assertOk()->assertSee($appointment->reference)->assertSee('#1 (Waiting)')->assertSee('Cancel Booking');

        $this->post('/book/my-booking/cancel', ['cancel_reason' => 'I have to leave'])->assertRedirect('/book/my-booking');
        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame('cancelled', PatientVisit::where('appointment_id', $appointment->id)->value('status'));   // off the board too
        $this->get('/book/my-booking')->assertDontSee('Cancel Booking');
    }

    public function test_no_cancelling_once_with_the_veterinarian(): void
    {
        $this->post('/book', $this->form());
        $appointment = Appointment::latest('id')->first();
        PatientVisit::where('appointment_id', $appointment->id)->update(['status' => 'ongoing']);

        $this->post('/book/check', ['reference' => $appointment->reference, 'contact_number' => '09181112222']);
        $this->get('/book/my-booking')->assertOk()->assertDontSee('Cancel Booking');
        $this->post('/book/my-booking/cancel', ['cancel_reason' => 'Too long'])->assertSessionHasErrors('cancel_reason');
        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_logged_in_users_use_their_own_pages_and_a_later_account_keeps_the_history(): void
    {
        $this->post('/book', $this->form(['email' => 'rosa@example.com']));
        $customer = Customer::where('contact_number', '09181112222')->first();

        // registering later with the same email links the guest record (pets and bookings stay)
        $this->post('/register', [
            'firstname' => 'Rosa', 'lastname' => 'Dizon', 'email' => 'rosa@example.com', 'mobile' => '09181112222',
            'password' => 'petlover1', 'password_confirmation' => 'petlover1', 'tnc' => '1',
        ])->assertRedirect('/register/verify');
        $code = Mail::sent(VerificationCodeMail::class)->last()->code;
        $this->post('/register/verify', ['code' => $code])->assertRedirect('/portal');

        $this->assertNotNull($customer->fresh()->user_id);
        // next visit to the portal (a fresh copy of the account, as on a new page load)
        $this->actingAs(User::where('email', 'rosa@example.com')->first());
        $this->get('/portal/appointments')->assertOk()->assertSee('Consultation, Deworming');
        $this->get('/book')->assertRedirect('/portal');   // logged in: use the portal instead
    }
}
