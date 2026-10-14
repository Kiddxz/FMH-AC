<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * People's names cannot have numbers or symbols, on every form.
 */
class PersonNamesTest extends TestCase
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

    public function test_register_refuses_numbers_in_the_names(): void
    {
        $form = [
            'firstname' => 'Mark2', 'lastname' => 'Pacamalan_1', 'email' => 'mark@example.com', 'mobile' => '09993847212',
            'password' => 'Mark1234', 'password_confirmation' => 'Mark1234', 'tnc' => '1',
        ];
        $this->from('/register')->post('/register', $form)->assertRedirect('/register')
            ->assertSessionHasErrors(['firstname', 'lastname']);
        $this->assertNull(User::where('email', 'mark@example.com')->first());

        // real names with ñ, a period, a hyphen, an apostrophe and spaces are fine
        $this->post('/register', ['firstname' => 'Ma. Niña', 'lastname' => "dela Cruz-O'Neil"] + $form)
            ->assertSessionDoesntHaveErrors(['firstname', 'lastname']);
    }

    public function test_walk_in_booking_refuses_numbers_in_the_names(): void
    {
        $this->post('/book', [
            'first_name' => 'R0sa', 'last_name' => 'Dizon3', 'contact_number' => '09181112222', 'email' => '',
            'pet_name' => 'K9', 'species' => 'dog', 'gender' => 'male', 'age' => 1,   // a pet may have a number
            'service_ids' => [Service::where('name', 'Consultation')->value('id')], 'privacy' => '1',
        ])->assertSessionHasErrors(['first_name', 'last_name'])->assertSessionDoesntHaveErrors('pet_name');
        $this->assertSame(0, Customer::where('contact_number', '09181112222')->count());
    }

    public function test_staff_and_super_admin_forms_refuse_numbers_too(): void
    {
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $john = Customer::where('first_name', 'John')->first();
        $this->actingAs($staff)->put("/staff/customers/{$john->id}", ['firstname' => 'John1', 'lastname' => 'Cruz'])
            ->assertSessionHasErrors('firstname');
        $this->assertSame('John', $john->fresh()->first_name);

        $this->actingAs($staff)->put('/staff/profile', ['firstname' => 'Ana', 'lastname' => 'Cruz99'])->assertSessionHasErrors('lastname');
    }

    public function test_pages_with_a_name_field_get_the_typing_check(): void
    {
        $this->get('/register')->assertOk()->assertSee('Numbers and symbols are not allowed in names.');
        $this->get('/book')->assertOk()->assertSee('Numbers and symbols are not allowed in names.');
        $this->get('/login')->assertOk()->assertDontSee('Numbers and symbols are not allowed in names.');
    }
}
