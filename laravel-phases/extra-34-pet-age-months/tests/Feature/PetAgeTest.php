<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The age of a pet is entered in years AND months (e.g. 2 years 3 months, or 0 years 5 months for a puppy).
 */
class PetAgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 09:30:00');   // a Monday, the clinic is open
        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_age_text_shows_years_and_months(): void
    {
        $pet = new Pet(['birthdate' => Pet::birthdateFromAge(2, 3)]);
        $this->assertSame('2 years 3 months old', $pet->age_text);
        $this->assertSame([2, 3], [$pet->age_years, $pet->age_months]);

        $this->assertSame('1 year old', (new Pet(['birthdate' => Pet::birthdateFromAge(1, 0)]))->age_text);
        $this->assertSame('5 months old', (new Pet(['birthdate' => Pet::birthdateFromAge(0, 5)]))->age_text);
        $this->assertSame('1 year 1 month old', (new Pet(['birthdate' => Pet::birthdateFromAge(1, 1)]))->age_text);
        $this->assertSame('Below 1 month old', (new Pet(['birthdate' => Pet::birthdateFromAge(0, 0)]))->age_text);
    }

    public function test_the_portal_saves_years_and_months(): void
    {
        $mark = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $this->actingAs($mark)->get('/portal/pets/create')->assertOk()->assertSee('name="age_months"', false);

        $this->actingAs($mark)->post('/portal/pets', [
            'petname' => 'Tiny', 'species' => 'cat', 'breed' => 'Puspin', 'sex' => 'female', 'age' => 0, 'age_months' => 4,
        ])->assertSessionHasNoErrors();
        $tiny = Pet::where('name', 'Tiny')->first();
        $this->assertSame('4 months old', $tiny->age_text);

        // the edit form shows the saved months, and 12 months is refused
        $this->actingAs($mark)->get("/portal/pets/{$tiny->id}/edit")->assertSee('<option value="4" selected', false);
        $this->actingAs($mark)->put("/portal/pets/{$tiny->id}", [
            'petname' => 'Tiny', 'species' => 'cat', 'breed' => 'Puspin', 'sex' => 'female', 'age' => 0, 'age_months' => 12,
        ])->assertSessionHasErrors('age_months');
    }

    public function test_the_walk_in_booking_saves_years_and_months(): void
    {
        $this->get('/book')->assertOk()->assertSee('name="age_months"', false);
        $this->post('/book', [
            'first_name' => 'Rosa', 'last_name' => 'Dizon', 'contact_number' => '09181112222', 'email' => '',
            'pet_name' => 'Bantay', 'species' => 'dog', 'breed' => 'Aspin', 'gender' => 'male', 'age' => 1, 'age_months' => 6,
            'service_ids' => [Service::where('name', 'Consultation')->value('id')], 'privacy' => '1',
        ])->assertRedirect('/book/done');

        $this->assertSame('1 year 6 months old', Pet::where('name', 'Bantay')->first()->age_text);
    }

    public function test_the_staff_can_change_the_months(): void
    {
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $max = Pet::where('name', 'Max')->first();

        $this->actingAs($staff)->put("/staff/pets/{$max->id}", [
            'petname' => 'Max', 'species' => $max->species, 'breed' => $max->breed ?: 'Aspin', 'sex' => $max->gender,
            'age' => 3, 'age_months' => 2, 'status' => 'active',
        ])->assertSessionHasNoErrors();
        $this->assertSame('3 years 2 months old', $max->fresh()->age_text);
    }
}
