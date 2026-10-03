<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 8: Super Admin user accounts, roles & permissions, oversight pages and profile.
 */
class SuperAdminModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->super = User::where('email', 'superadmin@fmhanimalclinic.com')->first();
    }

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->value('id');
    }

    public function test_dashboard_shows_real_numbers_and_activity(): void
    {
        $this->actingAs($this->super)->get('/superadmin')
            ->assertOk()
            ->assertSeeInOrder(['Total Users', '5', 'Appointments', '7', 'Transactions', '0', 'Inventory Items', '10']);
    }

    public function test_user_list_filters(): void
    {
        $this->actingAs($this->super)->get('/superadmin/users')->assertOk()->assertSee('Ana Cruz')->assertSee('Jose Ramos');
        $this->actingAs($this->super)->get('/superadmin/users?role=vet_admin')->assertOk()->assertSee('Jose Ramos')->assertDontSee('Ana Cruz');
        $this->actingAs($this->super)->get('/superadmin/users?search=assistant')->assertOk()->assertSee('Ana Cruz')->assertDontSee('Jose Ramos');
    }

    public function test_create_staff_account_that_can_log_in(): void
    {
        $this->actingAs($this->super)->post('/superadmin/users', [
            'firstname' => 'Bea', 'lastname' => 'Lim', 'email' => 'bea@fmhanimalclinic.com', 'mobile' => '09175556666',
            'role_id' => $this->roleId(Role::STAFF), 'password' => 'staffbea1', 'password_confirmation' => 'staffbea1',
        ])->assertRedirect('/superadmin/users');

        $bea = User::where('email', 'bea@fmhanimalclinic.com')->first();
        $this->assertSame('staff', $bea->role->slug);
        $this->assertNotNull($bea->email_verified_at);
        $this->assertDatabaseHas('activity_logs', ['module' => 'User Accounts', 'action' => 'created']);

        $this->post('/logout');
        $this->post('/login', ['email' => 'bea@fmhanimalclinic.com', 'password' => 'staffbea1'])->assertRedirect('/staff');
    }

    public function test_creating_a_customer_account_makes_a_customer_profile(): void
    {
        $this->actingAs($this->super)->post('/superadmin/users', [
            'firstname' => 'Cara', 'lastname' => 'Uy', 'email' => 'cara@example.com', 'mobile' => '09175557777',
            'role_id' => $this->roleId(Role::CUSTOMER), 'password' => 'carauy123', 'password_confirmation' => 'carauy123',
        ]);
        $this->assertNotNull(User::where('email', 'cara@example.com')->first()->customer);
    }

    public function test_validation_duplicate_email_and_weak_password(): void
    {
        $this->actingAs($this->super)->post('/superadmin/users', [
            'firstname' => 'X', 'lastname' => 'Y', 'email' => 'admin@fmhanimalclinic.com', 'mobile' => '123',
            'role_id' => 999, 'password' => 'short', 'password_confirmation' => 'other',
        ])->assertSessionHasErrors(['email', 'mobile', 'role_id', 'password']);
    }

    public function test_edit_user_change_role_and_reset_password(): void
    {
        $ana = User::where('email', 'assistant@fmhanimalclinic.com')->first();

        $this->actingAs($this->super)->put("/superadmin/users/{$ana->id}", [
            'firstname' => 'Ana', 'lastname' => 'Cruz', 'email' => 'assistant@fmhanimalclinic.com', 'mobile' => '09181234567',
            'role_id' => $this->roleId(Role::VET_ADMIN), 'password' => 'newanapass1', 'password_confirmation' => 'newanapass1',
        ])->assertRedirect('/superadmin/users');

        $ana->refresh();
        $this->assertSame('vet_admin', $ana->role->slug);
        $this->assertTrue(Hash::check('newanapass1', $ana->password));

        // empty password keeps the current one
        $this->actingAs($this->super)->put("/superadmin/users/{$ana->id}", [
            'firstname' => 'Ana', 'lastname' => 'Cruz', 'email' => 'assistant@fmhanimalclinic.com', 'mobile' => '09181234567',
            'role_id' => $this->roleId(Role::STAFF), 'password' => '', 'password_confirmation' => '',
        ]);
        $this->assertTrue(Hash::check('newanapass1', $ana->fresh()->password));
    }

    public function test_super_admin_cannot_lock_themselves_out(): void
    {
        $this->actingAs($this->super)->put("/superadmin/users/{$this->super->id}", [
            'firstname' => 'System', 'lastname' => 'Administrator', 'email' => $this->super->email, 'mobile' => '09170000001',
            'role_id' => $this->roleId(Role::CUSTOMER),
        ])->assertSessionHasErrors('role_id');
        $this->assertSame('super_admin', $this->super->fresh()->role->slug);

        $this->actingAs($this->super)->patch("/superadmin/users/{$this->super->id}/status")->assertSessionHasErrors('status');
        $this->assertTrue($this->super->fresh()->isActive());
    }

    public function test_deactivate_blocks_login_and_activate_restores(): void
    {
        $ana = User::where('email', 'assistant@fmhanimalclinic.com')->first();

        $this->actingAs($this->super)->patch("/superadmin/users/{$ana->id}/status");
        $this->assertSame('inactive', $ana->fresh()->status);

        $this->post('/logout');
        $this->post('/login', ['email' => $ana->email, 'password' => 'assistant123'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($this->super)->patch("/superadmin/users/{$ana->id}/status");
        $this->assertSame('active', $ana->fresh()->status);
    }

    public function test_roles_and_permissions_change_access(): void
    {
        $staffRole = Role::where('slug', Role::STAFF)->first();
        $ana = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->actingAs($ana)->get('/staff/pets')->assertOk();

        $this->actingAs($this->super)->get('/superadmin/roles')->assertOk()->assertSee('Staff/Receptionist')->assertSee('View pet profiles');

        // keep every staff permission except pets.view
        $keep = $staffRole->permissions()->where('slug', '!=', 'pets.view')->pluck('permissions.id')->all();
        $this->actingAs($this->super)->put("/superadmin/roles/{$staffRole->id}", ['permissions' => $keep])
            ->assertRedirect('/superadmin/roles');

        $this->actingAs($ana->fresh())->get('/staff/pets')->assertForbidden();
        $this->assertDatabaseHas('activity_logs', ['module' => 'Roles & Permissions']);
    }

    public function test_super_admin_role_keeps_protected_permissions(): void
    {
        $superRole = Role::where('slug', Role::SUPER_ADMIN)->first();

        $this->actingAs($this->super)->put("/superadmin/roles/{$superRole->id}", ['permissions' => []]);

        $slugs = $superRole->permissions()->pluck('slug');
        $this->assertTrue($slugs->contains('users.manage'));
        $this->assertTrue($slugs->contains('roles.manage'));
        $this->actingAs($this->super->fresh())->get('/superadmin/users')->assertOk();
    }

    public function test_oversight_pages_show_real_data_without_medical_notes(): void
    {
        $this->actingAs($this->super)->get('/superadmin/appointments?status=cancelled')->assertOk()->assertSee('Rocky')->assertDontSee('Buddy');
        $this->actingAs($this->super)->get('/superadmin/pet-records')->assertOk()->assertSee('Max')->assertSee('1 record')
            ->assertDontSee('Healthy - for vaccination.');
        $this->actingAs($this->super)->get('/superadmin/inventory')->assertOk()->assertSee('Cotton Balls')->assertSee('Out of Stock')->assertSee('Expiring soon');
        $this->actingAs($this->super)->get('/superadmin/activity-logs')->assertOk();
    }

    public function test_only_super_admin_reaches_these_pages(): void
    {
        $vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $this->actingAs($vet)->get('/superadmin/users')->assertForbidden();
        $this->actingAs($vet)->put('/superadmin/roles/1', ['permissions' => []])->assertForbidden();
        $this->actingAs($vet)->post('/superadmin/users', [])->assertForbidden();
    }

    public function test_super_admin_profile(): void
    {
        $this->actingAs($this->super)->get('/superadmin/profile')->assertOk()->assertSee('superadmin@fmhanimalclinic.com');
        $this->actingAs($this->super)->put('/superadmin/profile', ['firstname' => 'Sys', 'lastname' => 'Admin', 'mobile' => '09170000002'])
            ->assertRedirect('/superadmin/profile');
    }
}
