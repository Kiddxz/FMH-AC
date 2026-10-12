<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * BOOKING_DOMAIN=book.fmh.test: the booking without an account is its own website,
 * with its own home page, but it saves to the same database as the main website.
 */
class BookingWebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // read when the routes are loaded, so it is set before the application starts
        putenv('BOOKING_DOMAIN=book.fmh.test');
        $_ENV['BOOKING_DOMAIN'] = $_SERVER['BOOKING_DOMAIN'] = 'book.fmh.test';
        parent::setUp();
        Carbon::setTestNow('2026-10-05 07:00:00');
        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
        putenv('BOOKING_DOMAIN');
        unset($_ENV['BOOKING_DOMAIN'], $_SERVER['BOOKING_DOMAIN']);
    }

    public function test_the_booking_website_has_its_own_home_page_and_saves_to_the_same_database(): void
    {
        // its own home page at "/"
        $this->get('http://book.fmh.test/')->assertOk()->assertSee('Book a Visit for Your Pet')->assertSee('Online Booking');
        // the main website still has its own home page
        $this->get('http://localhost/')->assertOk()->assertSee('Caring For Your Pets Like')->assertDontSee('Book Without an Account');
        // the booking pages are not on the main website
        $this->get('http://localhost/book')->assertNotFound();

        $this->get('http://book.fmh.test/book')->assertOk()->assertSee('No account needed');
        $this->post('http://book.fmh.test/book', [
            'first_name' => 'Rosa', 'last_name' => 'Dizon', 'contact_number' => '09181112222', 'email' => '',
            'pet_name' => 'Bantay', 'species' => 'dog', 'breed' => 'Aspin', 'gender' => 'male', 'age' => 3,
            'service_ids' => [Service::where('name', 'Consultation')->value('id')],
            'appointment_date' => '2026-10-06', 'appointment_time' => '09:00', 'privacy' => '1',
        ])->assertRedirect('http://book.fmh.test/book/done');

        // the same database: the clinic sees it in its appointments
        $appointment = Appointment::latest('id')->first();
        $this->assertSame('pending', $appointment->status);
        $this->assertSame('Dizon', $appointment->customer->last_name);
    }
}
