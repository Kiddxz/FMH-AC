<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Profile, Edit Profile and Change Password are three separate pages for every user.
 */
class ProfilePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function accounts(): array
    {
        return [
            ['owner@fmhanimalclinic.com', 'portal'],
            ['assistant@fmhanimalclinic.com', 'staff'],
            ['admin@fmhanimalclinic.com', 'admin'],
            ['superadmin@fmhanimalclinic.com', 'superadmin'],
        ];
    }

    public function test_edit_profile_page_only_has_the_profile_form(): void
    {
        foreach ($this->accounts() as [$email, $area]) {
            $this->actingAs(User::where('email', $email)->first())->get("/{$area}/profile/edit")
                ->assertOk()
                ->assertSee('Edit Profile')->assertSee('name="firstname"', false)
                ->assertDontSee('name="current_password"', false);
        }
    }

    public function test_change_password_page_only_has_the_password_form(): void
    {
        foreach ($this->accounts() as [$email, $area]) {
            $this->actingAs(User::where('email', $email)->first())->get("/{$area}/profile/password")
                ->assertOk()
                ->assertSee('Change Password')->assertSee('name="current_password"', false)
                ->assertDontSee('name="firstname"', false);
        }
    }

    public function test_profile_page_links_to_both_pages_and_has_no_role_numbers(): void
    {
        foreach ($this->accounts() as [$email, $area]) {
            $this->actingAs(User::where('email', $email)->first())->get("/{$area}/profile")
                ->assertOk()
                ->assertSee(route($area . '.profile.edit'))
                ->assertSee(route($area . '.password.edit'))
                ->assertDontSee('Backups Made')->assertDontSee('Active Accounts');
        }
    }

    public function test_a_wrong_current_password_goes_back_to_the_change_password_page(): void
    {
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->actingAs($staff)->from('/staff/profile/password')->put('/staff/profile/password', [
            'current_password' => 'wrong-pass1', 'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertRedirect('/staff/profile/password')->assertSessionHasErrors('current_password');
    }
}
