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
 * Demo mode (PAYMONGO_DEMO=true): the whole online payment can be shown without a PayMongo account.
 */
class PayMongoDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 07:00:00');
        $this->seed(DatabaseSeeder::class);
        config(['paymongo.secret_key' => null, 'paymongo.demo' => true]);
        Http::fake();   // PayMongo is never called in demo mode
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function book(): Transaction
    {
        $this->post('/book', [
            'first_name' => 'Rosa', 'last_name' => 'Dizon', 'contact_number' => '09181112222', 'email' => '',
            'pet_name' => 'Bantay', 'species' => 'dog', 'breed' => 'Aspin', 'gender' => 'male', 'age' => 3,
            'service_ids' => [Service::where('name', 'Consultation')->value('id')],
            'appointment_date' => '2026-10-06', 'appointment_time' => '09:00', 'privacy' => '1', 'payment_method' => 'online',
        ]);

        return Transaction::where('appointment_id', Appointment::latest('id')->value('id'))->firstOrFail();
    }

    public function test_the_whole_online_payment_works_in_demo_mode(): void
    {
        $this->get('/book')->assertOk()->assertDontSee('Not available right now');
        $bill = $this->book();

        $this->get(route('pay.show', $bill->pay_token))->assertOk()->assertSee('DEMO MODE');
        $this->post(route('pay.checkout', $bill->pay_token))->assertRedirect(route('pay.demo', $bill->pay_token));
        $this->get(route('pay.demo', $bill->pay_token))->assertOk()->assertSee('No real money');

        $this->post(route('pay.demo.pay', $bill->pay_token), ['method' => 'gcash'])->assertRedirect(route('pay.done', $bill->pay_token));
        $this->get(route('pay.done', $bill->pay_token))->assertRedirect(route('pay.show', $bill->pay_token));

        $bill->refresh();
        $this->assertSame('paid', $bill->status);
        $payment = Payment::where('transaction_id', $bill->id)->first();
        $this->assertSame(Payment::ONLINE, $payment->method);
        $this->assertStringStartsWith('pay_demo_', $payment->reference_number);   // easy to see that it was a demo
        Http::assertNothingSent();

        // opening /done again does not save it twice
        $this->get(route('pay.done', $bill->pay_token));
        $this->assertSame(1, Payment::where('transaction_id', $bill->id)->count());
    }

    public function test_demo_pages_are_closed_when_demo_mode_is_off_or_on_the_live_system(): void
    {
        $bill = $this->book();
        $this->post(route('pay.checkout', $bill->pay_token));

        config(['paymongo.demo' => false]);
        $this->get(route('pay.demo', $bill->pay_token))->assertNotFound();

        config(['paymongo.demo' => true]);
        $this->app['env'] = 'production';
        $this->get(route('pay.demo', $bill->pay_token))->assertNotFound();
        $this->assertFalse(\App\Services\PayMongo::demo());   // the "Pay" button uses the same check
        $this->assertSame('unpaid', $bill->fresh()->status);
    }

    public function test_a_real_key_turns_demo_mode_off(): void
    {
        config(['paymongo.secret_key' => 'sk_test_fake']);
        $this->assertFalse(\App\Services\PayMongo::demo());
    }
}
