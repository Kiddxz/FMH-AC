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
 * Phase 5: customer dashboard, profile, password and My Pets.
 */
class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $mark;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->mark = User::where('email', 'owner@fmhanimalclinic.com')->first();
    }

    private function newCustomer(): User
    {
        $user = User::factory()->create(['first_name' => 'Other']);
        $customer = new Customer(['first_name' => 'Other', 'last_name' => 'Owner', 'contact_number' => '09170000001']);
        $customer->user_id = $user->id;
        $customer->save();

        return $user->fresh();
    }

    private function petData(array $overrides = []): array
    {
        return array_merge([
            'petname' => 'Choco', 'species' => 'dog', 'breed' => 'Aspin', 'sex' => 'male',
            'age' => 2, 'color' => 'Brown', 'notes' => 'Friendly',
        ], $overrides);
    }

    public function test_dashboard_shows_real_counts(): void
    {
        $this->actingAs($this->mark)->get('/portal')
            ->assertOk()
            ->assertSee('Welcome, Mark!')
            ->assertSeeInOrder(['2', 'Registered Pets', '2', 'Upcoming Appointments', '1', 'Completed Appointments']);
    }

    public function test_my_pets_lists_only_own_pets(): void
    {
        $this->actingAs($this->mark)->get('/portal/pets')
            ->assertOk()->assertSee('Max')->assertSee('Luna')->assertDontSee('Buddy')->assertDontSee('Coco');

        $this->actingAs($this->newCustomer())->get('/portal/pets')
            ->assertOk()->assertSee('No Pets Yet')->assertDontSee('Max');
    }

    public function test_add_pet_is_saved_to_the_logged_in_owner(): void
    {
        $other = Customer::where('first_name', 'John')->first();

        $this->actingAs($this->mark)->post('/portal/pets', $this->petData(['customer_id' => $other->id]))
            ->assertRedirect('/portal/pets')->assertSessionHas('status');

        $pet = Pet::where('name', 'Choco')->first();
        $this->assertSame($this->mark->customer->id, $pet->customer_id);   // not John's
        $this->assertSame('male', $pet->gender);
        $this->assertSame('2 years old', $pet->age_text);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Pets', 'action' => 'created']);
    }

    public function test_add_pet_validation(): void
    {
        $this->actingAs($this->mark)->post('/portal/pets', ['petname' => '', 'species' => 'dragon', 'age' => -1])
            ->assertSessionHasErrors(['petname', 'species', 'breed', 'sex', 'age']);
        $this->assertSame(0, Pet::where('species', 'dragon')->count());
    }

    public function test_owner_can_view_and_edit_own_pet(): void
    {
        $max = Pet::where('name', 'Max')->first();
        $birthdate = $max->birthdate->toDateString();

        $this->actingAs($this->mark)->get("/portal/pets/{$max->id}")
            ->assertOk()->assertSee('Golden Retriever')->assertSee('Rabies Vaccine')->assertSee('After vaccination care');
        $this->actingAs($this->mark)->get("/portal/pets/{$max->id}/edit")->assertOk()->assertSee('value="3"', false);

        // same age -> birthdate is kept
        $this->actingAs($this->mark)->put("/portal/pets/{$max->id}", $this->petData(['petname' => 'Max', 'age' => 3, 'breed' => 'Golden Retriever Mix']))
            ->assertRedirect("/portal/pets/{$max->id}");
        $max->refresh();
        $this->assertSame('Golden Retriever Mix', $max->breed);
        $this->assertSame($birthdate, $max->birthdate->toDateString());

        // new age -> birthdate changes
        $this->actingAs($this->mark)->put("/portal/pets/{$max->id}", $this->petData(['petname' => 'Max', 'age' => 5]));
        $this->assertSame('5 years old', $max->fresh()->age_text);
    }

    public function test_owner_cannot_reach_another_owners_pet(): void
    {
        $buddy = Pet::where('name', 'Buddy')->first();

        $this->actingAs($this->mark)->get("/portal/pets/{$buddy->id}")->assertForbidden();
        $this->actingAs($this->mark)->get("/portal/pets/{$buddy->id}/edit")->assertForbidden();
        $this->actingAs($this->mark)->put("/portal/pets/{$buddy->id}", $this->petData(['petname' => 'Stolen']))->assertForbidden();
        $this->assertSame('Buddy', $buddy->fresh()->name);
        $this->actingAs($this->mark)->get('/portal/pets/999999')->assertNotFound();
    }

    public function test_profile_update_syncs_customer_record(): void
    {
        $this->actingAs($this->mark)->get('/portal/profile')->assertOk()->assertSee('Mark Santos')->assertSee('Las Piñas City');

        $this->actingAs($this->mark)->put('/portal/profile', [
            'firstname' => 'Marky', 'lastname' => 'Santos', 'mobile' => '09998887777', 'address' => 'BF Resort, Las Piñas',
            'email' => 'hacked@example.com',
        ])->assertRedirect('/portal/profile');

        $this->mark->refresh();
        $this->assertSame('Marky', $this->mark->first_name);
        $this->assertSame('owner@fmhanimalclinic.com', $this->mark->email);       // email not changeable here
        $this->assertSame('09998887777', $this->mark->customer->contact_number);
        $this->assertSame('BF Resort, Las Piñas', $this->mark->customer->address);

        $this->actingAs($this->mark)->put('/portal/profile', ['firstname' => 'M', 'lastname' => 'S', 'mobile' => '123'])
            ->assertSessionHasErrors('mobile');
    }

    public function test_change_password_rules(): void
    {
        $this->actingAs($this->mark)->put('/portal/profile/password', [
            'current_password' => 'wrong', 'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertSessionHasErrors(['current_password' => 'Your current password is incorrect.']);

        $this->actingAs($this->mark)->put('/portal/profile/password', [
            'current_password' => 'owner123', 'password' => 'owner123', 'password_confirmation' => 'owner123',
        ])->assertSessionHasErrors(['password' => 'Your new password must be different from your current password.']);

        $this->actingAs($this->mark)->put('/portal/profile/password', [
            'current_password' => 'owner123', 'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertRedirect('/portal/profile');

        $this->assertTrue(Hash::check('newpass123', $this->mark->fresh()->password));
    }

    public function test_staff_cannot_use_customer_pet_pages(): void
    {
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->actingAs($staff)->post('/portal/pets', $this->petData())->assertForbidden();
        $this->actingAs($staff)->get('/portal/pets/1')->assertForbidden();
    }
}
