<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Booking without an account: the information is saved like a walk-in customer.
 */
class GuestBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 07:00:00');   // a Monday, before opening time
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
            'appointment_date' => '2026-10-06', 'appointment_time' => '09:00', 'reason' => 'Check-up', 'privacy' => '1',
        ];
    }

    public function test_a_pet_owner_books_without_an_account(): void
    {
        $this->get('/')->assertSee('Book Without an Account');
        $this->get('/book')->assertOk()->assertSee('No account needed')->assertSee('name="service_ids[]"', false);
        $this->getJson('/book/slots?date=2026-10-06')->assertOk()->assertJson(['open' => true]);

        $this->post('/book', $this->form())->assertRedirect('/book/done');

        $appointment = Appointment::latest('id')->first();
        $this->assertSame('pending', $appointment->status);
        $this->assertSame('Consultation, Deworming', $appointment->service_names);
        $this->assertNull($appointment->created_by);

        // saved in the database like a walk-in: customer (no login) + pet
        $customer = $appointment->customer;
        $this->assertSame('Rosa Dizon', $customer->full_name);
        $this->assertTrue($customer->is_walk_in);
        $this->assertNull($customer->user_id);
        $this->assertSame('Bantay', $appointment->pet->name);

        $this->get('/book/done')->assertOk()->assertSee($appointment->reference)->assertSee('₱850.00');

        // the clinic sees it right away
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->actingAs($staff)->get('/notifications')->assertSee('New booking to confirm: Bantay');
        $this->get('/staff/customers?search=Dizon')->assertSee('Walk-in');
    }

    public function test_the_same_owner_booking_again_keeps_one_record(): void
    {
        $this->post('/book', $this->form());
        $this->post('/book', $this->form(['first_name' => 'ROSA', 'appointment_time' => '10:00']));

        $this->assertSame(1, Customer::where('contact_number', '09181112222')->count());
        $this->assertSame(1, Customer::where('contact_number', '09181112222')->first()->pets()->count());
        $this->assertSame(2, Appointment::whereHas('customer', fn ($q) => $q->where('contact_number', '09181112222'))->count());
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

    public function test_the_owner_checks_and_cancels_with_the_reference_and_mobile_number(): void
    {
        $this->post('/book', $this->form());
        $appointment = Appointment::latest('id')->first();

        $this->post('/book/check', ['reference' => $appointment->reference, 'contact_number' => '09990000000'])
            ->assertSessionHasErrors(['reference' => 'No booking matches this reference number and mobile number.']);
        $this->get('/book/my-booking')->assertRedirect('/book/check');

        $this->post('/book/check', ['reference' => strtolower($appointment->reference), 'contact_number' => '09181112222'])
            ->assertRedirect('/book/my-booking');
        $this->get('/book/my-booking')->assertOk()->assertSee($appointment->reference)->assertSee('Consultation, Deworming')->assertSee('Cancel Booking');

        $this->post('/book/my-booking/cancel', ['cancel_reason' => 'Schedule conflict'])->assertRedirect('/book/my-booking');
        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->get('/book/my-booking')->assertDontSee('Cancel Booking');
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
