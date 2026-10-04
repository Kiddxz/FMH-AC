<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 18: security pass (NFR-REQ011, NFR-REQ012).
 * The route check below looks at EVERY address of the system, so a new page that
 * forgets its login or role guard makes this test fail.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // Login, register and password reset pages: only for people who are NOT logged in
    private const PUBLIC_ROUTES = [
        'login', 'login.attempt', 'register', 'register.store', 'register.verify', 'register.verify.check',
        'register.resend', 'password.request', 'password.email', 'password.reset', 'password.update',
    ];

    // Each area and the only role allowed in it
    private const AREAS = [
        'portal/' => 'role:customer',
        'staff/' => 'role:staff',
        'admin/' => 'role:vet_admin',
        'superadmin/' => 'role:super_admin',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->first();
    }

    public function test_every_inside_page_needs_a_login_and_the_right_role(): void
    {
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            $middleware = $route->gatherMiddleware();

            // The home page, Laravel's own health check, and the old login addresses that only redirect to /login
            if (in_array($uri, ['/', 'up', 'admin/login', 'staff/login', 'superadmin/login'])) {
                continue;
            }
            if (in_array($route->getName(), self::PUBLIC_ROUTES)) {
                $this->assertContains('guest', $middleware, "{$uri} is a login/register page and must be for guests only");
                continue;
            }

            $this->assertContains('auth', $middleware, "{$uri} must need a login");
            foreach (self::AREAS as $prefix => $role) {
                if (Str::startsWith($uri, $prefix)) {
                    $this->assertContains($role, $middleware, "{$uri} must only be for {$role}");
                }
            }
            $checked++;
        }

        $this->assertGreaterThan(150, $checked);
    }

    public function test_pages_send_the_security_headers(): void
    {
        $response = $this->get('/login')->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
        $response->assertHeaderMissing('Strict-Transport-Security');   // only on https
    }

    public function test_pages_seen_while_logged_in_are_not_saved_by_the_browser(): void
    {
        $response = $this->actingAs($this->user('assistant@fmhanimalclinic.com'))->get('/staff')->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_backup_files_cannot_be_opened_by_a_web_address(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->get('/storage/backups/anything.json')->assertNotFound();
    }

    public function test_the_error_page_hides_technical_details(): void
    {
        config(['app.debug' => false]);
        Route::get('/_test-error', fn () => throw new \RuntimeException('secret database detail'))->middleware('web');

        $this->get('/_test-error')->assertStatus(500)
            ->assertSee('Something Went Wrong')
            ->assertDontSee('secret database detail')
            ->assertDontSee('RuntimeException');
    }

    public function test_the_security_check_command_lists_what_to_fix(): void
    {
        $this->artisan('fmh:security-check')
            ->expectsOutputToContain('superadmin@fmhanimalclinic.com does not use its demo password')
            ->assertExitCode(0);
    }
}
