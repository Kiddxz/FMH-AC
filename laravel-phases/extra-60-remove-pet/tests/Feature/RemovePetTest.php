<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Pet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A pet owner can remove a pet added by mistake, but never one that has clinic records.
 */
class RemovePetTest extends TestCase
{
    use RefreshDatabase;

    private User $mark;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->mark = User::where('email', 'owner@fmhanimalclinic.com')->first();
    }

    private function newPet(string $name = 'Sae'): Pet
    {
        $this->actingAs($this->mark)->post('/portal/pets', [
            'petname' => $name, 'species' => 'cat', 'breed' => 'Persian', 'sex' => 'female', 'age' => 1, 'age_months' => 5,
        ])->assertRedirect('/portal/pets');

        return Pet::where('name', $name)->latest('id')->first();
    }

    public function test_owner_can_remove_a_pet_without_records(): void
    {
        $pet = $this->newPet();

        $this->actingAs($this->mark)->get("/portal/pets/{$pet->id}")->assertOk()->assertSee('Remove Sae');
        $this->actingAs($this->mark)->delete("/portal/pets/{$pet->id}")->assertRedirect('/portal/pets');

        $this->assertSoftDeleted($pet);
        $this->actingAs($this->mark)->get('/portal/pets')->assertViewHas('pets', fn ($pets) => $pets->doesntContain(fn ($p) => $p->is($pet)));
        $this->assertDatabaseHas('activity_logs', ['module' => 'Pets', 'action' => 'deleted']);
    }

    public function test_a_pet_with_clinic_records_cannot_be_removed(): void
    {
        $pet = Customer::where('user_id', $this->mark->id)->first()->pets()->whereHas('appointments')->first();

        $this->actingAs($this->mark)->get("/portal/pets/{$pet->id}")->assertOk()->assertDontSee('Remove ' . $pet->name)->assertSee('cannot be removed');
        $this->actingAs($this->mark)->delete("/portal/pets/{$pet->id}")->assertSessionHasErrors('pet');
        $this->assertNotSoftDeleted($pet);
    }

    public function test_another_owner_cannot_remove_my_pet(): void
    {
        $pet = $this->newPet();
        $other = User::where('email', 'assistant@fmhanimalclinic.com')->first();   // not the owner

        $this->actingAs($other)->delete("/portal/pets/{$pet->id}")->assertForbidden();
        $this->assertNotSoftDeleted($pet);
    }

    public function test_the_same_pet_cannot_be_added_twice(): void
    {
        $this->newPet();

        $this->actingAs($this->mark)->post('/portal/pets', [
            'petname' => 'sae ', 'species' => 'cat', 'breed' => 'Persian', 'sex' => 'female', 'age' => 1,
        ])->assertSessionHasErrors(['petname' => 'You already have a cat named sae. Please check My Pets.']);
        $this->assertSame(1, Pet::where('name', 'Sae')->count());
    }
}
