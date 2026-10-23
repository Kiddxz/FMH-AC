<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * BOOKING_DOMAIN=walkin.fmh.test: the booking without an account is its own website,
 * with its own home page, but it saves to the same database as the main website.
 */
class BookingWebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // read when the routes are loaded, so it is set before the application starts
        putenv('BOOKING_DOMAIN=walkin.fmh.test');
        $_ENV['BOOKING_DOMAIN'] = $_SERVER['BOOKING_DOMAIN'] = 'walkin.fmh.test';
        parent::setUp();
        Carbon::setTestNow('2026-10-05 09:30:00');   // a Monday, the clinic is open
        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
        // back to no booking domain (phpunit.xml), even if the .env of the computer has one
        putenv('BOOKING_DOMAIN=');
        $_ENV['BOOKING_DOMAIN'] = $_SERVER['BOOKING_DOMAIN'] = '';
    }

    public function test_the_booking_website_has_its_own_home_page_and_saves_to_the_same_database(): void
    {
        // its own home page at "/"
        $this->get('http://walkin.fmh.test/')->assertOk()->assertSee('get your queue number')->assertSee('Become a Registered Client');
        // the main website still has its own home page
        $this->get('http://localhost/')->assertOk()->assertSee('Caring For Your Pets Like')->assertDontSee('Book Without an Account');
        // the booking pages are not on the main website
        $this->get('http://localhost/book')->assertNotFound();

        $this->get('http://walkin.fmh.test/book')->assertOk()->assertSee('Book Walk-in Visit');
        $this->post('http://walkin.fmh.test/book', [
            'first_name' => 'Rosa', 'last_name' => 'Dizon', 'contact_number' => '09181112222', 'email' => '',
            'pet_name' => 'Bantay', 'species' => 'dog', 'breed' => 'Aspin', 'gender' => 'male', 'age' => 3,
            'service_ids' => [Service::where('name', 'Consultation')->value('id')],
            'privacy' => '1',
        ])->assertRedirect('http://walkin.fmh.test/book/done');

        // the same database: the clinic sees it in its appointments
        $appointment = Appointment::latest('id')->first();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame('Dizon', $appointment->customer->last_name);
    }

    public function test_the_walk_in_website_shows_only_the_booking_and_pay_pages(): void
    {
        // the booking pages open
        $this->get('http://walkin.fmh.test/')->assertOk();
        $this->get('http://walkin.fmh.test/book')->assertOk();
        $this->get('http://walkin.fmh.test/book/check')->assertOk();

        // every other page of the system gives "Page Not Found" on the walk-in website
        foreach (['/login', '/register', '/forgot-password', '/portal', '/portal/pets', '/staff', '/staff/patient-flow',
            '/admin', '/admin/services', '/superadmin', '/superadmin/users', '/admin/login', '/notifications', '/no-such-page'] as $path) {
            $this->get('http://walkin.fmh.test' . $path)->assertNotFound();
        }
        $this->post('http://walkin.fmh.test/login', ['email' => 'admin@fmhanimalclinic.com', 'password' => 'admin123'])->assertNotFound();
        $this->assertGuest();

        // even someone logged in cannot open the clinic pages there
        $admin = \App\Models\User::where('email', 'admin@fmhanimalclinic.com')->first();
        $this->actingAs($admin)->get('http://walkin.fmh.test/admin')->assertNotFound();
        auth()->logout();

        // the pay link of a bill still works there (walk-in customers pay online)
        $this->get('http://walkin.fmh.test/pay/not-a-real-token')->assertNotFound();   // unknown bill: still 404
        $bill = \App\Models\Transaction::firstOrFail();
        $bill->forceFill(['pay_token' => str_repeat('w', 40)])->save();
        $this->get('http://walkin.fmh.test/pay/' . $bill->pay_token)->assertOk()->assertSee($bill->receipt_number);

        // the main website still works as before
        $this->get('http://localhost/login')->assertOk();
        $this->get('http://localhost/admin')->assertRedirect();
    }
}
