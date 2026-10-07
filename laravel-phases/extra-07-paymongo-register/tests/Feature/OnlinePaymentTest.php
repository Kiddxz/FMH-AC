<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PosService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Online payment with PayMongo (requested by the adviser).
 * PayMongo itself is faked here, so the tests need no internet and no real key.
 */
class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        config(['paymongo.secret_key' => 'sk_test_fake']);
    }

    // An unpaid walk-in bill: 1 Consultation (₱500)
    private function unpaidBill(): Transaction
    {
        $consultation = Service::where('name', 'Consultation')->first();

        return app(PosService::class)->createBill(
            ['customer_id' => null, 'pet_id' => null, 'appointment_id' => null, 'patient_visit_id' => null, 'transaction_type' => 'counter', 'discount' => 0],
            [['type' => 'service', 'id' => $consultation->id, 'quantity' => 1]],
            null,
            $this->staff->id,
        );
    }

    private function fakePayMongo(array $payments = []): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response(['data' => [
                'id' => 'cs_test_123',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_123'],
            ]]),
            'api.paymongo.com/v1/checkout_sessions/cs_test_123' => Http::response(['data' => [
                'id' => 'cs_test_123',
                'attributes' => ['payments' => $payments],
            ]]),
        ]);
    }

    public function test_staff_makes_a_pay_link_and_the_customer_sees_the_bill_without_an_account(): void
    {
        $bill = $this->unpaidBill();

        $this->actingAs($this->staff)->get('/staff/transactions/' . $bill->id)->assertOk()->assertSee('Make Pay Link');
        $this->post('/staff/transactions/' . $bill->id . '/pay-link')->assertRedirect();
        $token = $bill->fresh()->pay_token;
        $this->assertSame(40, strlen($token));
        // the QR code and the link are shown; "Check" appears only after the customer opened PayMongo
        $this->get('/staff/transactions/' . $bill->id)->assertSee('/pay/' . $token)->assertSee('pay-qr')->assertDontSee('Check Online Payment');

        // the customer (not logged in) opens the link
        $this->post('/logout');
        $this->get('/pay/' . $token)->assertOk()
            ->assertSee($bill->receipt_number)->assertSee('Consultation')->assertSee('₱500.00')->assertSee('Pay ₱500.00 Online');

        // a wrong or guessed token shows nothing
        $this->get('/pay/' . str_repeat('a', 40))->assertNotFound();
        $this->get('/pay/123')->assertNotFound();
    }

    public function test_paying_online_saves_the_payment_once_after_paymongo_confirms_it(): void
    {
        $bill = $this->unpaidBill();
        $bill->forceFill(['pay_token' => str_repeat('t', 40)])->save();
        $this->fakePayMongo([['id' => 'pay_test_1', 'attributes' => ['status' => 'paid', 'amount' => 50000]]]);

        // "Pay Online" goes to the PayMongo page, with the bill's lines and our return addresses
        $this->post('/pay/' . $bill->pay_token)->assertRedirect('https://checkout.paymongo.com/cs_test_123');
        Http::assertSent(function (Request $request) use ($bill) {
            $attributes = $request['data']['attributes'];

            return $request->hasHeader('Authorization', 'Basic ' . base64_encode('sk_test_fake:'))
                && $attributes['line_items'][0]['amount'] === 50000
                && $attributes['line_items'][0]['name'] === 'Consultation'
                && $attributes['reference_number'] === $bill->receipt_number
                && str_ends_with($attributes['success_url'], '/pay/' . $bill->pay_token . '/done');
        });
        $this->assertSame('cs_test_123', $bill->fresh()->paymongo_checkout_id);

        // back from PayMongo: PayMongo is asked, and the payment is saved
        $this->get('/pay/' . $bill->pay_token . '/done')->assertRedirect('/pay/' . $bill->pay_token);
        $bill->refresh();
        $this->assertSame('paid', $bill->status);
        $this->assertSame('0.00', $bill->balance);
        $payment = $bill->payments()->first();
        $this->assertSame(Payment::ONLINE, $payment->method);
        $this->assertSame('pay_test_1', $payment->reference_number);
        $this->assertNull($payment->received_by);
        $this->get('/pay/' . $bill->pay_token)->assertSee('Fully paid');

        // opening the page again, or the cashier's "Check", does not save it twice
        $this->get('/pay/' . $bill->pay_token . '/done');
        $this->actingAs($this->staff)->post('/staff/transactions/' . $bill->id . '/pay-check')->assertSessionHas('status', 'No new online payment yet.');
        $this->assertSame(1, $bill->payments()->count());
        $this->assertTrue(ActivityLog::where('description', 'like', '%online (PayMongo pay_test_1)%')->exists());

        // shown on the bill, the receipt and the transaction list as PayMongo
        $this->get('/staff/transactions/' . $bill->id)->assertSee('PayMongo (online)');
        $this->get('/staff/transactions/' . $bill->id . '/receipt')->assertSee('PayMongo (online)');
        $this->get('/staff/transactions?method=paymongo')->assertOk()->assertSee($bill->receipt_number);
    }

    public function test_the_return_address_alone_never_marks_a_bill_paid(): void
    {
        $bill = $this->unpaidBill();
        $bill->forceFill(['pay_token' => str_repeat('u', 40), 'paymongo_checkout_id' => 'cs_test_123'])->save();
        $this->fakePayMongo([['id' => 'pay_test_2', 'attributes' => ['status' => 'failed', 'amount' => 50000]]]);

        $this->get('/pay/' . $bill->pay_token . '/done')->assertSessionHas('status', fn ($m) => str_contains($m, 'not received the payment yet'));
        $this->assertSame('unpaid', $bill->fresh()->status);
        $this->assertSame(0, $bill->payments()->count());
    }

    public function test_online_payment_is_hidden_without_a_key_and_the_cashier_cannot_type_paymongo(): void
    {
        config(['paymongo.secret_key' => null]);
        $bill = $this->unpaidBill();

        $this->actingAs($this->staff)->get('/staff/transactions/' . $bill->id)->assertDontSee('Make Pay Link');
        $this->post('/staff/transactions/' . $bill->id . '/pay-link')->assertSessionHasErrors('paymongo');
        $this->assertNull($bill->fresh()->pay_token);

        // "paymongo" is saved only from PayMongo's answer, never chosen by hand
        $this->post('/staff/transactions/' . $bill->id . '/payments', ['amount' => 500, 'method' => 'paymongo', 'reference_number' => 'x'])
            ->assertSessionHasErrors('method');
    }
}
