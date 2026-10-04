<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 18 (UI polish): every panel uses the new menu, shows who is logged in,
 * and every menu link is on the page (nothing is hidden).
 */
class LayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->first();
    }

    public function test_panels_show_the_logged_in_user_and_the_menu_button(): void
    {
        $panels = [
            'assistant@fmhanimalclinic.com' => ['/staff', 'Ana Cruz', 'Staff/Receptionist'],
            'admin@fmhanimalclinic.com' => ['/admin', 'Maria Santos', 'Veterinarian/Admin'],
            'superadmin@fmhanimalclinic.com' => ['/superadmin', 'System Administrator', 'Super Admin'],
        ];

        foreach ($panels as $email => [$url, $name, $role]) {
            $this->actingAs($this->user($email))->get($url)->assertOk()
                ->assertSee('css/fmh-ui.css')
                ->assertSee('class="nav-toggle"', false)
                ->assertSee('id="panelMenu"', false)
                ->assertSee($name)
                ->assertSee($role)
                ->assertSee('Logout');
        }
    }

    public function test_super_admin_menu_has_every_page(): void
    {
        $page = $this->actingAs($this->user('superadmin@fmhanimalclinic.com'))->get('/superadmin')->assertOk();
        foreach (['users', 'appointments', 'pet-records', 'waivers', 'transactions', 'inventory', 'reports', 'activity-logs', 'backups', 'settings', 'profile', 'logout'] as $path) {
            $page->assertSee(url('/superadmin/' . $path));
        }
    }

    public function test_customer_portal_uses_the_polish_stylesheet(): void
    {
        $this->actingAs($this->user('owner@fmhanimalclinic.com'))->get('/portal')->assertOk()
            ->assertSee('css/fmh-ui.css')
            ->assertSee('fmh-app');
    }
}
