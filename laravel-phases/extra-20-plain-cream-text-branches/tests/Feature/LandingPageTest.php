<?php

namespace Tests\Feature;

use App\Models\ClinicSchedule;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Home page: the clinic's services (clickable), clinic hours, branches and social links.
 */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_the_home_page_lists_every_service_of_the_clinic(): void
    {
        $page = $this->get('/')->assertOk();
        foreach (config('clinic.services') as $service) {
            $page->assertSee($service['name']);
        }
        $this->assertCount(15, config('clinic.services'));

        // bookable services open the booking form with that service already checked
        $vaccination = Service::where('name', 'Vaccination')->value('id');
        $page->assertSee(route('guest.book', ['service' => $vaccination]), false);
        $this->get('/book?service=' . $vaccination)->assertOk()
            ->assertSee('value="' . $vaccination . '" data-price', false)
            ->assertSee('class="service-option checked"', false);

        // services that need the vet first are not bookable online
        $page->assertSee('Visit the clinic')->assertSee('This service needs the veterinarian to see your pet first');
    }

    public function test_clinic_hours_come_from_the_settings(): void
    {
        // the clinic's real hours: Mon-Sat 8:00 AM - 7:00 PM, Sun 8:00 AM - 3:00 PM
        ClinicSchedule::whereBetween('day_of_week', [1, 6])->update(['is_open' => true, 'opens_at' => '08:00:00', 'closes_at' => '19:00:00']);
        ClinicSchedule::where('day_of_week', 0)->update(['is_open' => true, 'opens_at' => '08:00:00', 'closes_at' => '15:00:00']);

        $html = preg_replace('/\s+/', ' ', $this->get('/')->assertOk()->getContent());
        $this->assertStringContainsString('<strong>Mon – Sat</strong><span>8:00 AM – 7:00 PM</span>', $html);
        $this->assertStringContainsString('<strong>Sun</strong><span>8:00 AM – 3:00 PM</span>', $html);

        // and the booking uses the same hours: 6:30 PM is now a time slot
        $slots = collect($this->getJson('/book/slots?date=' . now()->next('Tuesday')->toDateString())->json('slots'))->pluck('time');
        $this->assertTrue($slots->contains('18:30'));
    }

    public function test_branches_and_social_links_are_shown(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Main Branch')->assertSee('Molino Branch')->assertSee("Queen's Row Branch")
            ->assertSee('Online booking')
            ->assertSee('@fmh.animalclinic')->assertSee('https://www.tiktok.com/@fmhanimalclinic', false)
            ->assertSee('Get in Touch')->assertSee('tel:09323145969', false)
            ->assertDontSee('image/bg.jpg', false)->assertDontSee('branch-photo', false);
    }

    public function test_a_service_photo_is_used_when_the_file_is_added(): void
    {
        $path = public_path('image/services/xray.jpg');
        $had = is_file($path);
        if (! $had) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, 'test');
        }
        try {
            $this->get('/')->assertSee('data-photo="' . asset('image/services/xray.jpg') . '"', false);
        } finally {
            if (! $had) {
                File::delete($path);
            }
        }
        $this->get('/')->assertSee('data-photo=""', false);   // the others still use their icon
    }

    public function test_services_are_a_slider_with_filters(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('fmh-pets.png', false)
            ->assertSee('data-filter="book"', false)->assertSee('data-filter="visit"', false)
            ->assertSee('id="serviceSlider"', false)->assertSee('id="serviceNext"', false)
            ->assertSee('data-kind="book"', false)->assertSee('data-kind="visit"', false);
    }
}
