<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7: Veterinarian / Admin dashboard, services, customers (read-only), pets and profile.
 */
class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $vet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
    }

    public function test_dashboard_shows_real_numbers(): void
    {
        $this->actingAs($this->vet)->get('/admin')
            ->assertOk()
            ->assertSee('Welcome back, Dr. Santos!')
            ->assertSeeInOrder(['2', "Today's Appointments", '7', 'Registered Pets', '6', 'Pet Owners', '3', 'Pending Appointments'])
            ->assertSee('Pet Records')->assertSee('Profile');   // new menu links
    }

    public function test_services_list_and_filter(): void
    {
        $this->actingAs($this->vet)->get('/admin/services')
            ->assertOk()->assertSee('Consultation')->assertSee('₱500.00')->assertSee('Nail Trimming')->assertSee('Inactive');

        $this->actingAs($this->vet)->get('/admin/services?purpose=grooming')
            ->assertOk()->assertSee('Grooming')->assertDontSee('Vaccination service');
    }

    public function test_add_edit_and_toggle_service(): void
    {
        $this->actingAs($this->vet)->post('/admin/services', [
            'name' => 'Dental Cleaning', 'purpose' => 'treatment', 'description' => 'Teeth cleaning',
            'price' => '1200', 'duration_minutes' => 45, 'is_active' => '1',
        ])->assertRedirect('/admin/services');

        $dental = Service::where('name', 'Dental Cleaning')->first();
        $this->assertSame('1200.00', $dental->price);
        $this->assertTrue($dental->is_active);

        $this->actingAs($this->vet)->put("/admin/services/{$dental->id}", [
            'name' => 'Dental Cleaning', 'purpose' => 'treatment', 'price' => '1500', 'duration_minutes' => 60, 'is_active' => '0',
        ])->assertRedirect('/admin/services');
        $dental->refresh();
        $this->assertSame('1500.00', $dental->price);
        $this->assertFalse($dental->is_active);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Services', 'action' => 'updated']);

        $this->actingAs($this->vet)->patch("/admin/services/{$dental->id}/toggle");
        $this->assertTrue($dental->fresh()->is_active);
    }

    public function test_service_validation(): void
    {
        $this->actingAs($this->vet)->post('/admin/services', [
            'name' => 'Consultation', 'purpose' => 'surgery', 'price' => '-5', 'duration_minutes' => 0,
        ])->assertSessionHasErrors(['name', 'purpose', 'price', 'duration_minutes']);
    }

    public function test_used_service_cannot_be_deleted_but_unused_can(): void
    {
        $consultation = Service::where('name', 'Consultation')->first();   // used by appointments
        $this->actingAs($this->vet)->delete("/admin/services/{$consultation->id}")->assertSessionHasErrors('service');
        $this->assertNotNull($consultation->fresh());

        $nails = Service::where('name', 'Nail Trimming')->first();         // never used
        $this->actingAs($this->vet)->delete("/admin/services/{$nails->id}")->assertRedirect('/admin/services');
        $this->assertNull($nails->fresh());
    }

    public function test_customers_are_read_only_for_the_vet(): void
    {
        $mark = Customer::where('first_name', 'Mark')->first();

        $this->actingAs($this->vet)->get('/admin/customers')->assertOk()->assertSee('Mark Santos')->assertDontSee('+ Add New User');
        $this->actingAs($this->vet)->get('/admin/customers?type=walk_in')->assertOk()->assertSee('Maria Lopez')->assertDontSee('Mark Santos');
        $this->actingAs($this->vet)->get("/admin/customers/{$mark->id}")->assertOk()->assertSee('Max')->assertDontSee('Edit Customer');

        // the staff edit address is not reachable by the vet
        $this->actingAs($this->vet)->put("/staff/customers/{$mark->id}", ['firstname' => 'X'])->assertForbidden();
    }

    public function test_pet_list_filters_and_profile_with_medical_records(): void
    {
        $this->actingAs($this->vet)->get('/admin/pets?gender=female')->assertOk()->assertSee('Luna')->assertDontSee('Rocky');
        $this->actingAs($this->vet)->get('/admin/pets?search=persian')->assertOk()->assertSee('Coco')->assertDontSee('Buddy');

        $max = Pet::where('name', 'Max')->first();
        $this->actingAs($this->vet)->get("/admin/pets/{$max->id}")
            ->assertOk()->assertSee('Healthy - for vaccination.')->assertSee('Rabies Vaccine')->assertSee('Mark Santos');
    }

    public function test_staff_cannot_manage_services(): void
    {
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->actingAs($staff)->get('/admin/services')->assertForbidden();
        $this->actingAs($staff)->post('/admin/services', ['name' => 'Hack'])->assertForbidden();
    }

    public function test_admin_profile_edit(): void
    {
        $this->actingAs($this->vet)->get('/admin/profile')->assertOk()->assertSee('Dr. Maria Santos')->assertSee('Veterinarian/Admin');
        $this->actingAs($this->vet)->put('/admin/profile', ['firstname' => 'Maria', 'lastname' => 'Santos-Reyes', 'mobile' => '09170000000'])
            ->assertRedirect('/admin/profile');
        $this->assertSame('Santos-Reyes', $this->vet->fresh()->last_name);
    }
}
