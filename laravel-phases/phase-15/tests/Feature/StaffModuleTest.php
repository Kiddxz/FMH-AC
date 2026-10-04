<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Pet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 6: Staff / Receptionist dashboard, customer directory, pets and profile.
 */
class StaffModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
    }

    public function test_dashboard_shows_real_numbers_and_todays_appointments(): void
    {
        $this->actingAs($this->staff)->get('/staff')
            ->assertOk()
            ->assertSee('Staff Dashboard')
            ->assertDontSee('Assistant')
            ->assertSeeInOrder(['Appointments Today', '2', 'Pending Appointments', '3', 'Registered Pets', '7', 'Completed Today', '0'])
            ->assertSee('Mark Santos')->assertSee('Buddy')
            // Coco's appointment is tomorrow, so it is not in today's list
            // (Coco may still appear under "Recent Pet Registrations", added in Phase 15)
            ->assertViewHas('todaysAppointments', fn ($list) => $list->pluck('pet.name')->doesntContain('Coco'));
    }

    public function test_customer_directory_search(): void
    {
        $this->actingAs($this->staff)->get('/staff/customers')->assertOk()->assertSee('Mark Santos')->assertSee('James Garcia');

        $this->actingAs($this->staff)->get('/staff/customers?search=garcia')
            ->assertOk()->assertSee('James Garcia')->assertSee('Sarah Garcia')->assertDontSee('Mark Santos');

        $this->actingAs($this->staff)->get('/staff/customers?search=09191234567')
            ->assertOk()->assertSee('Anna Reyes')->assertDontSee('John Cruz');

        $this->actingAs($this->staff)->get('/staff/customers?search=sarah+garcia')
            ->assertOk()->assertSee('Sarah Garcia')->assertDontSee('James Garcia');
    }

    public function test_customer_profile_and_edit(): void
    {
        $mark = Customer::where('first_name', 'Mark')->first();
        $maria = Customer::where('first_name', 'Maria')->first();   // walk-in

        $this->actingAs($this->staff)->get("/staff/customers/{$mark->id}")
            ->assertOk()->assertSee('Max')->assertSee('Luna')->assertSee('Has a customer portal account.');

        // portal customer: email stays, account name follows
        $this->actingAs($this->staff)->put("/staff/customers/{$mark->id}", [
            'firstname' => 'Marcus', 'lastname' => 'Santos', 'mobile' => '09170001111', 'address' => 'Pamplona', 'email' => 'x@x.com',
        ])->assertRedirect("/staff/customers/{$mark->id}");
        $mark->refresh();
        $this->assertSame('owner@fmhanimalclinic.com', $mark->email);
        $this->assertSame('Marcus', $mark->user->first_name);
        $this->assertSame('09170001111', $mark->user->contact_number);

        // walk-in: email can be set
        $this->actingAs($this->staff)->put("/staff/customers/{$maria->id}", [
            'firstname' => 'Maria', 'lastname' => 'Lopez', 'mobile' => '09201234567', 'email' => 'maria.new@example.com',
        ]);
        $this->assertSame('maria.new@example.com', $maria->fresh()->email);
    }

    public function test_pet_list_search_and_species_filter(): void
    {
        $this->actingAs($this->staff)->get('/staff/pets')->assertOk()->assertSee('Rocky')->assertSee('Milo');
        $this->actingAs($this->staff)->get('/staff/pets?species=cat')->assertOk()->assertSee('Luna')->assertDontSee('Rocky');
        $this->actingAs($this->staff)->get('/staff/pets?search=santos')->assertOk()->assertSee('Max')->assertSee('Luna')->assertDontSee('Buddy');
        $this->actingAs($this->staff)->get('/staff/pets?search=persian')->assertOk()->assertSee('Coco')->assertDontSee('Max');
    }

    public function test_pet_record_shows_vaccinations_but_not_vet_diagnosis(): void
    {
        $max = Pet::where('name', 'Max')->first();

        $this->actingAs($this->staff)->get("/staff/pets/{$max->id}")
            ->assertOk()
            ->assertSee('Rabies Vaccine')
            ->assertSee('Mark Santos')
            ->assertDontSee('Healthy - for vaccination.');   // vet-only medical note (decision P4)
    }

    public function test_staff_can_edit_pet_profile(): void
    {
        $rocky = Pet::where('name', 'Rocky')->first();

        $this->actingAs($this->staff)->get("/staff/pets/{$rocky->id}/edit")->assertOk();
        $this->actingAs($this->staff)->put("/staff/pets/{$rocky->id}", [
            'petname' => 'Rocky', 'species' => 'dog', 'breed' => 'Aspin Mix', 'sex' => 'male', 'age' => 4, 'status' => 'archived',
        ])->assertRedirect("/staff/pets/{$rocky->id}");

        $rocky->refresh();
        $this->assertSame('Aspin Mix', $rocky->breed);
        $this->assertSame('archived', $rocky->status);
        $this->assertSame($rocky->customer_id, Customer::where('first_name', 'James')->value('id'));

        $this->actingAs($this->staff)->put("/staff/pets/{$rocky->id}", ['petname' => 'X', 'status' => 'lost'])
            ->assertSessionHasErrors(['status', 'species', 'breed', 'sex', 'age']);
    }

    public function test_staff_profile_edit_and_password(): void
    {
        $this->actingAs($this->staff)->get('/staff/profile')->assertOk()->assertSee('Ana Cruz')->assertSee('Staff/Receptionist');

        $this->actingAs($this->staff)->put('/staff/profile', ['firstname' => 'Anna', 'lastname' => 'Cruz', 'mobile' => '09181112222'])
            ->assertRedirect('/staff/profile');
        $this->assertSame('Anna', $this->staff->fresh()->first_name);

        $this->actingAs($this->staff)->put('/staff/profile/password', [
            'current_password' => 'assistant123', 'password' => 'staffpass1', 'password_confirmation' => 'staffpass1',
        ])->assertRedirect('/staff/profile');
        $this->assertTrue(Hash::check('staffpass1', $this->staff->fresh()->password));
    }

    public function test_removing_customers_manage_hides_editing(): void
    {
        $role = $this->staff->role;
        $role->permissions()->detach(\App\Models\Permission::where('slug', 'customers.manage')->value('id'));
        $this->staff->refresh();
        $mark = Customer::where('first_name', 'Mark')->first();

        $this->actingAs($this->staff)->get("/staff/customers/{$mark->id}")->assertOk()->assertDontSee('Edit Customer');
        $this->actingAs($this->staff)->get("/staff/customers/{$mark->id}/edit")->assertForbidden();
    }
}
