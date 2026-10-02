<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\Customer;
use App\Models\User;
use App\Models\VerificationCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private ?string $lastCode = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, UserSeeder::class]);
        Mail::fake();
    }

    private function sentCode(string $email): string
    {
        $code = null;
        Mail::assertSent(VerificationCodeMail::class, function ($mail) use ($email, &$code) {
            if ($mail->hasTo($email)) {
                $code = $mail->code;
            }

            return true;
        });

        return $code;
    }

    public function test_each_role_logs_in_and_lands_on_its_dashboard(): void
    {
        $cases = [
            ['owner@fmhanimalclinic.com', 'owner123', '/portal'],
            ['assistant@fmhanimalclinic.com', 'assistant123', '/staff'],
            ['admin@fmhanimalclinic.com', 'admin123', '/admin'],
            ['superadmin@fmhanimalclinic.com', 'superadmin123', '/superadmin'],
        ];

        foreach ($cases as [$email, $password, $home]) {
            $this->post('/login', ['email' => $email, 'password' => $password])
                ->assertRedirect($home);
            $this->assertAuthenticated();
            $this->assertNotNull(User::where('email', $email)->first()->last_login_at);
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }

        $this->assertDatabaseHas('activity_logs', ['action' => 'login']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'logout']);
    }

    public function test_wrong_password_is_rejected_and_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'wrong'])
                ->assertRedirect('/login')
                ->assertSessionHasErrors(['email' => 'Invalid email or password.']);
        }

        // 6th try: locked even with the right password
        $this->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'owner123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $user = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $user->status = 'inactive';
        $user->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'assistant123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_protected_pages_need_login_and_guests_pages_redirect_logged_in_users(): void
    {
        foreach (['/portal', '/staff', '/admin', '/superadmin/users', '/portal/profile'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->actingAs($staff)->get('/login')->assertRedirect('/staff');
        $this->actingAs($staff)->get('/register')->assertRedirect('/staff');
        $this->get('/logout')->assertStatus(405); // logout must be POST
    }

    public function test_registration_with_email_code(): void
    {
        $this->post('/register', [
            'firstname' => 'Liza',
            'lastname' => 'Soberano',
            'email' => 'liza@example.com',
            'mobile' => '09171231234',
            'password' => 'petlover1',
            'password_confirmation' => 'petlover1',
            'tnc' => '1',
        ])->assertRedirect('/register/verify');

        $user = User::where('email', 'liza@example.com')->first();
        $this->assertNull($user->email_verified_at);
        $this->assertSame('customer', $user->role->slug);
        $this->assertNotSame('petlover1', $user->password);   // hashed
        $code = $this->sentCode('liza@example.com');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, VerificationCode::first()->code);   // stored hashed

        // Login before verifying -> sent back to the verify page
        $this->post('/login', ['email' => 'liza@example.com', 'password' => 'petlover1'])
            ->assertRedirect('/register/verify');
        $this->assertGuest();
        $code = Mail::sent(VerificationCodeMail::class)->last()->code;   // a new code was sent

        // wrong code
        $wrong = $code === '000000' ? '111111' : '000000';
        $this->post('/register/verify', ['code' => $wrong])->assertSessionHasErrors(['code' => 'The code you entered is incorrect.']);

        $this->post('/register/verify', ['code' => $code])->assertRedirect('/portal');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame('09171231234', $user->fresh()->customer->contact_number);

        // the same code cannot be used twice / email is now taken
        $this->post('/logout');
        $this->post('/register', [
            'firstname' => 'X', 'lastname' => 'Y', 'email' => 'liza@example.com', 'mobile' => '09171231234',
            'password' => 'petlover1', 'password_confirmation' => 'petlover1', 'tnc' => '1',
        ])->assertSessionHasErrors(['email' => 'This email is already registered. Please log in instead.']);
    }

    public function test_registration_validation_and_role_cannot_be_injected(): void
    {
        $this->post('/register', [
            'firstname' => '', 'lastname' => 'A', 'email' => 'bad', 'mobile' => '12345',
            'password' => 'short', 'password_confirmation' => 'other', 'role_id' => 1,
        ])->assertSessionHasErrors(['firstname', 'email', 'mobile', 'password', 'tnc']);

        $this->post('/register', [
            'firstname' => 'Hack', 'lastname' => 'Er', 'email' => 'hacker@example.com', 'mobile' => '09170000000',
            'password' => 'hacker123', 'password_confirmation' => 'hacker123', 'tnc' => '1',
            'role_id' => 1, 'status' => 'active', 'email_verified_at' => now(),
        ]);
        $user = User::where('email', 'hacker@example.com')->first();
        $this->assertSame('customer', $user->role->slug);
        $this->assertNull($user->email_verified_at);
    }

    public function test_verification_links_existing_walk_in_record(): void
    {
        $walkIn = Customer::create([
            'first_name' => 'Pedro', 'last_name' => 'Penduko', 'contact_number' => '09998887777',
            'email' => 'pedro@example.com', 'is_walk_in' => true,
        ]);

        $this->post('/register', [
            'firstname' => 'Pedro', 'lastname' => 'Penduko', 'email' => 'pedro@example.com', 'mobile' => '09998887777',
            'password' => 'pedro1234', 'password_confirmation' => 'pedro1234', 'tnc' => '1',
        ]);
        $this->post('/register/verify', ['code' => $this->sentCode('pedro@example.com')])->assertRedirect('/portal');

        $this->assertSame($walkIn->id, User::where('email', 'pedro@example.com')->first()->customer->id);
        $this->assertSame(1, Customer::where('email', 'pedro@example.com')->count());
    }

    public function test_code_blocks_after_five_wrong_attempts(): void
    {
        $this->post('/forgot-password', ['email' => 'owner@fmhanimalclinic.com'])->assertRedirect('/reset-password');
        $code = $this->sentCode('owner@fmhanimalclinic.com');
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->post('/reset-password', ['code' => $wrong, 'password' => 'newpass123', 'password_confirmation' => 'newpass123']);
        }
        $this->post('/reset-password', ['code' => $code, 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
            ->assertSessionHasErrors(['code' => 'Too many wrong attempts. Please request a new code.']);
    }

    public function test_forgot_password_reset_flow_and_new_must_differ_from_old(): void
    {
        // unknown email: same message, no mail
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertRedirect('/reset-password');
        Mail::assertNothingSent();

        $this->post('/forgot-password', ['email' => 'owner@fmhanimalclinic.com'])->assertRedirect('/reset-password');
        $code = $this->sentCode('owner@fmhanimalclinic.com');

        // same as old password -> rejected, code still usable
        $this->post('/reset-password', ['code' => $code, 'password' => 'owner123', 'password_confirmation' => 'owner123'])
            ->assertSessionHasErrors(['password' => 'Your new password must be different from your old password.']);

        $this->post('/reset-password', ['code' => $code, 'password' => 'newowner123', 'password_confirmation' => 'newowner123'])
            ->assertRedirect('/login');

        $owner = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $this->assertTrue(Hash::check('newowner123', $owner->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'password_reset', 'user_id' => $owner->id]);

        // the code was used once
        $this->post('/forgot-password', ['email' => 'nobody@example.com']); // put an email in the session again
        $this->withSession(['reset_email' => 'owner@fmhanimalclinic.com'])
            ->post('/reset-password', ['code' => $code, 'password' => 'another123', 'password_confirmation' => 'another123'])
            ->assertSessionHasErrors(['code' => 'No active code found. Please request a new code.']);

        $this->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'owner123'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'newowner123'])->assertRedirect('/portal');
    }

    public function test_remember_me_sets_cookie(): void
    {
        $response = $this->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'owner123', 'rememberme' => '1']);
        $names = array_map(fn ($c) => $c->getName(), $response->headers->getCookies());
        $this->assertTrue(collect($names)->contains(fn ($n) => str_starts_with($n, 'remember_web_')));
    }

    public function test_intended_page_is_used_only_inside_the_users_own_area(): void
    {
        $this->get('/portal/pets')->assertRedirect('/login');
        $this->post('/login', ['email' => 'assistant@fmhanimalclinic.com', 'password' => 'assistant123'])->assertRedirect('/staff');
        $this->post('/logout');

        $this->get('/portal/pets')->assertRedirect('/login');
        $this->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'owner123'])->assertRedirect('/portal/pets');
    }

    public function test_failed_logins_do_not_block_forgot_password(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->post('/login', ['email' => 'x@example.com', 'password' => 'bad']);
        }
        $this->post('/forgot-password', ['email' => 'owner@fmhanimalclinic.com'])->assertRedirect('/reset-password');
        $this->post('/register', [])->assertSessionHasErrors('email');
    }
}
