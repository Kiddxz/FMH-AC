<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Pet;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Phase 4: every role x every address of every area.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private const AREAS = [
        'customer' => '/portal',
        'staff' => '/staff',
        'vet_admin' => '/admin',
        'super_admin' => '/superadmin',
    ];

    private const ACCOUNTS = [
        'customer' => 'owner@fmhanimalclinic.com',
        'staff' => 'assistant@fmhanimalclinic.com',
        'vet_admin' => 'admin@fmhanimalclinic.com',
        'super_admin' => 'superadmin@fmhanimalclinic.com',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    // All GET addresses of one area, with {parameters} filled in
    private function urlsOf(string $prefix): array
    {
        $urls = [];
        foreach (Route::getRoutes() as $route) {
            $uri = '/' . ltrim($route->uri(), '/');
            if (in_array('GET', $route->methods(), true) && ($uri === $prefix || str_starts_with($uri, $prefix . '/'))
                && ! str_ends_with($uri, '/login')) {
                $urls[] = preg_replace('/\{[^}]+\}/', '1', $uri);
            }
        }

        return $urls;
    }

    public function test_every_role_reaches_only_its_own_area(): void
    {
        $checked = 0;
        foreach (self::ACCOUNTS as $role => $email) {
            $user = User::where('email', $email)->first();

            foreach (self::AREAS as $areaRole => $prefix) {
                foreach ($this->urlsOf($prefix) as $url) {
                    $response = $this->actingAs($user)->get($url);
                    if ($role === $areaRole) {
                        $response->assertOk();
                    } else {
                        $response->assertForbidden();
                        $response->assertSee('Access Denied');
                    }
                    $checked++;
                }
            }
        }

        $this->assertGreaterThan(150, $checked);
    }

    public function test_guests_are_sent_to_login_for_every_area(): void
    {
        foreach (self::AREAS as $prefix) {
            foreach ($this->urlsOf($prefix) as $url) {
                $this->get($url)->assertRedirect('/login');
            }
        }
    }

    public function test_post_forms_are_protected_too(): void
    {
        $staff = User::where('email', self::ACCOUNTS['staff'])->first();
        $this->actingAs($staff)->post('/portal/pets', [])->assertForbidden();
        $this->actingAs($staff)->post('/portal/appointments', [])->assertForbidden();
    }

    public function test_removing_a_permission_blocks_the_page(): void
    {
        $staffRole = Role::where('slug', Role::STAFF)->first();
        $staff = User::where('email', self::ACCOUNTS['staff'])->first();

        $this->actingAs($staff)->get('/staff/pets')->assertOk();

        $staffRole->permissions()->detach(Permission::where('slug', 'pets.view')->value('id'));
        $staff->refresh();

        $this->actingAs($staff)->get('/staff/pets')->assertForbidden();
        $this->actingAs($staff)->get('/staff')->assertOk();   // dashboard needs only the role
    }

    public function test_deactivated_user_is_logged_out_on_next_click(): void
    {
        $staff = User::where('email', self::ACCOUNTS['staff'])->first();
        $this->actingAs($staff)->get('/staff')->assertOk();

        $staff->status = 'inactive';
        $staff->save();

        $this->actingAs($staff->fresh())->get('/staff')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pet_and_appointment_ownership_policies(): void
    {
        $mark = User::where('email', self::ACCOUNTS['customer'])->first();   // owns Max and Luna
        $other = User::factory()->create();                                  // a customer with no pets
        Customer::create(['first_name' => 'O', 'last_name' => 'T', 'contact_number' => '09170000009'])
            ->forceFill(['user_id' => $other->id])->save();
        $other->refresh();

        $max = Pet::where('name', 'Max')->first();
        $buddy = Pet::where('name', 'Buddy')->first();     // John's pet
        $marksAppointment = Appointment::where('pet_id', $max->id)->where('status', 'pending')->first();
        $completed = Appointment::where('pet_id', $max->id)->where('status', 'completed')->first();

        $this->assertTrue($mark->can('view', $max));
        $this->assertTrue($mark->can('update', $max));
        $this->assertFalse($mark->can('view', $buddy));
        $this->assertFalse($other->can('view', $max));
        $this->assertFalse($other->can('update', $max));

        $this->assertTrue($mark->can('view', $marksAppointment));
        $this->assertTrue($mark->can('cancel', $marksAppointment));
        $this->assertFalse($mark->can('cancel', $completed));
        $this->assertFalse($mark->can('update', $marksAppointment));
        $this->assertFalse($other->can('view', $marksAppointment));

        $staff = User::where('email', self::ACCOUNTS['staff'])->first();
        $super = User::where('email', self::ACCOUNTS['super_admin'])->first();
        $this->assertTrue($staff->can('update', $buddy));
        $this->assertTrue($staff->can('update', $marksAppointment));
        $this->assertTrue($super->can('view', $buddy));
        $this->assertFalse($super->can('update', $buddy));               // read-only oversight (P1)
        $this->assertFalse($super->can('update', $marksAppointment));
    }

    public function test_permission_abilities_follow_the_role(): void
    {
        $staff = User::where('email', self::ACCOUNTS['staff'])->first();
        $vet = User::where('email', self::ACCOUNTS['vet_admin'])->first();

        $this->assertTrue($staff->can('pos.manage'));
        $this->assertFalse($vet->can('pos.manage'));        // decision P2
        $this->assertTrue($vet->can('records.write'));
        $this->assertFalse($staff->can('records.write'));   // decision P4
    }

    public function test_error_pages_use_clinic_design(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page Not Found')->assertSee('FMH Animal Clinic');
    }
}
