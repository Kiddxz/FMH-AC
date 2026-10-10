<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Booking without an account + "Pay online now" with PayMongo (GCash, Maya, card).
 */
class GuestOnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 07:00:00');   // a Monday, before opening time
        $this->seed(DatabaseSeeder::class);
        config(['paymongo.secret_key' => 'sk_test_fake']);
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
            'appointment_date' => '2026-10-06', 'appointment_time' => '09:00', 'privacy' => '1',
            'payment_method' => 'online',
        ];
    }

    private function fakePayMongo(array $payments = []): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response(['data' => [
                'id' => 'cs_test_guest',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_guest'],
            ]]),
            'api.paymongo.com/v1/checkout_sessions/cs_test_guest' => Http::response(['data' => [
                'id' => 'cs_test_guest',
                'attributes' => ['payments' => $payments],
            ]]),
        ]);
    }

    public function test_the_owner_books_and_pays_online_without_an_account(): void
    {
        $this->get('/book')->assertOk()->assertSee('Pay online now')->assertSee('value="online"', false);

        $response = $this->post('/book', $this->form());
        $appointment = Appointment::latest('id')->first();
        $bill = Transaction::where('appointment_id', $appointment->id)->first();

        // a bill of the chosen services (no cashier yet), and the owner goes to its pay page
        $this->assertNotNull($bill);
        $this->assertSame('unpaid', $bill->status);
        $this->assertNull($bill->cashier_id);
        $this->assertSame(2, $bill->items()->count());
        $this->assertEquals($appointment->total_price, (float) $bill->total);
        $response->assertRedirect(route('pay.show', $bill->pay_token));
        $this->assertStringContainsString('Payment: online', $appointment->notes);

        $this->get(route('pay.show', $bill->pay_token))->assertOk()->assertSee('Pay ₱' . number_format($bill->total, 2) . ' Online')->assertSee('Check your booking')->assertSee($appointment->reference);

        // to PayMongo, then back: PayMongo is asked if it was really paid
        $this->fakePayMongo([['id' => 'pay_guest_1', 'attributes' => ['status' => 'paid', 'amount' => (int) round($bill->total * 100)]]]);
        $this->post(route('pay.checkout', $bill->pay_token))->assertRedirect('https://checkout.paymongo.com/cs_test_guest');
        $this->get(route('pay.done', $bill->pay_token))->assertRedirect(route('pay.show', $bill->pay_token));

        $bill->refresh();
        $this->assertSame('paid', $bill->status);
        $this->assertSame(1, Payment::where('method', Payment::ONLINE)->where('reference_number', 'pay_guest_1')->count());

        // the booking page shows it as paid
        $this->get('/book/done')->assertOk()->assertSee('Paid online');
    }

    public function test_pay_at_the_clinic_makes_no_bill(): void
    {
        $bills = Transaction::count();
        $this->post('/book', $this->form(['payment_method' => 'clinic']))->assertRedirect('/book/done');
        $this->assertSame($bills, Transaction::count());
        $this->get('/book/done')->assertSee('(pay at the clinic)');
    }

    public function test_online_payment_is_not_offered_without_the_paymongo_key(): void
    {
        config(['paymongo.secret_key' => null]);
        $appointments = Appointment::count();
        $this->get('/book')->assertOk()->assertSee('Not available right now');
        $this->post('/book', $this->form())->assertSessionHasErrors('payment_method');
        $this->assertSame($appointments, Appointment::count());   // nothing was booked
    }

    public function test_the_owner_can_pay_later_and_a_cancelled_booking_voids_the_unpaid_bill(): void
    {
        $this->post('/book', $this->form());
        $appointment = Appointment::latest('id')->first();
        $bill = Transaction::where('appointment_id', $appointment->id)->first();

        // later, from "Check your booking"
        $this->post('/book/check', ['reference' => $appointment->reference, 'contact_number' => '09181112222']);
        $this->get('/book/my-booking')->assertOk()->assertSee('Pay Online Now')->assertSee(route('pay.show', $bill->pay_token), false);

        $this->post('/book/my-booking/cancel', ['cancel_reason' => 'Schedule conflict'])->assertRedirect('/book/my-booking');
        $this->assertSame('void', $bill->fresh()->status);
        $this->assertSame('cancelled', $appointment->fresh()->status);
    }

    public function test_a_paid_booking_that_is_cancelled_is_flagged_for_a_refund(): void
    {
        $this->post('/book', $this->form());
        $appointment = Appointment::latest('id')->first();
        $bill = Transaction::where('appointment_id', $appointment->id)->first();
        $this->fakePayMongo([['id' => 'pay_guest_2', 'attributes' => ['status' => 'paid', 'amount' => (int) round($bill->total * 100)]]]);
        $this->post(route('pay.checkout', $bill->pay_token));
        $this->get(route('pay.done', $bill->pay_token));

        $this->post('/book/check', ['reference' => $appointment->reference, 'contact_number' => '09181112222']);
        $this->post('/book/my-booking/cancel', ['cancel_reason' => 'Pet is fine now'])
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'refund'));

        $this->assertSame('paid', $bill->fresh()->status);   // the money stays recorded until the clinic refunds it
        $this->assertDatabaseHas('activity_logs', ['module' => 'POS']);
    }
}
