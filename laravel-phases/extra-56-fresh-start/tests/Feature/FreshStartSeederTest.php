<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\FreshStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The clean start has the clinic accounts and setup, but no example customers or records.
 */
class FreshStartSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_start_has_no_example_customers_but_the_clinic_works(): void
    {
        $this->seed(FreshStartSeeder::class);

        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Pet::count());
        $this->assertSame(0, Appointment::count());
        $this->assertSame(0, InventoryItem::count());
        $this->assertSame(4, User::count());
        $this->assertGreaterThan(0, Service::count());

        foreach (['superadmin@fmhanimalclinic.com' => '/superadmin', 'admin@fmhanimalclinic.com' => '/admin', 'assistant@fmhanimalclinic.com' => '/staff'] as $email => $home) {
            $this->actingAs(User::where('email', $email)->first())->get($home)->assertOk();
            $this->actingAs(User::where('email', $email)->first())->get($home . '/appointments')->assertOk();
        }
        auth()->logout();
        $this->get('/register')->assertOk();   // pet owners can sign up by themselves
    }
}
