<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\RealEmail;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Register page: the mobile number (09 + 11 digits, numbers only) and no fake emails.
 */
class RegisterChecksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        RealEmail::$domainCheck = null;
        File::delete(RealEmail::blocklistPath() . '.test-backup');
        RealEmail::forgetBlocklist();
        parent::tearDown();
    }

    // Runs $test with a blocklist file made of $domains, then puts the real file back
    private function withBlocklist(string $content, callable $test): void
    {
        $path = RealEmail::blocklistPath();
        $had = is_file($path);
        if ($had) {
            File::copy($path, $path . '.test-backup');
        }
        try {
            File::put($path, $content);
            RealEmail::forgetBlocklist();
            $test();
        } finally {
            $had ? File::move($path . '.test-backup', $path) : File::delete($path);
            RealEmail::forgetBlocklist();
        }
    }

    private function register(array $changes): \Illuminate\Testing\TestResponse
    {
        return $this->post('/register', $changes + [
            'firstname' => 'Juan', 'lastname' => 'Dela Cruz', 'email' => 'juan@gmail.com', 'mobile' => '09171234567',
            'password' => 'petlover1', 'password_confirmation' => 'petlover1', 'tnc' => '1',
        ]);
    }

    public function test_mobile_number_must_be_numbers_only_start_with_09_and_have_11_digits(): void
    {
        $this->register(['mobile' => '0917abc4567'])->assertSessionHasErrors(['mobile' => 'The mobile number must contain numbers only (no letters, spaces or symbols).']);
        $this->register(['mobile' => '0917 123 4567'])->assertSessionHasErrors(['mobile' => 'The mobile number must contain numbers only (no letters, spaces or symbols).']);
        $this->register(['mobile' => '+639171234567'])->assertSessionHasErrors(['mobile' => 'The mobile number must contain numbers only (no letters, spaces or symbols).']);
        $this->register(['mobile' => '08171234567'])->assertSessionHasErrors(['mobile' => 'The mobile number must start with 09 (e.g. 09171234567).']);
        $this->register(['mobile' => '19171234567'])->assertSessionHasErrors(['mobile' => 'The mobile number must start with 09 (e.g. 09171234567).']);
        $this->register(['mobile' => '0917123456'])->assertSessionHasErrors(['mobile' => 'The mobile number must be exactly 11 digits (e.g. 09171234567).']);
        $this->register(['mobile' => '091712345678'])->assertSessionHasErrors(['mobile' => 'The mobile number must be exactly 11 digits (e.g. 09171234567).']);
        $this->assertNull(User::where('email', 'juan@gmail.com')->first());

        $this->register(['mobile' => '09171234567'])->assertRedirect('/register/verify');
    }

    public function test_fake_emails_cannot_register(): void
    {
        $this->register(['email' => 'juan@gmial.com'])->assertSessionHasErrors(['email' => 'Did you mean juan@gmail.com? Please check your email address.']);
        $this->register(['email' => 'juan@mailinator.com'])->assertSessionHasErrors(['email' => 'This is a temporary (fake) email address. Please use your real email, like Gmail or Yahoo.']);
        $this->register(['email' => 'juan@yopmail.com'])->assertSessionHasErrors('email');

        // a domain that does not exist on the internet (the internet check is faked here)
        RealEmail::$domainCheck = fn (string $domain) => $domain !== 'walangganitongdomain.com';
        $this->register(['email' => 'juan@walangganitongdomain.com'])->assertSessionHasErrors(['email' => 'This email address does not exist. Please check the part after the @.']);
        $this->assertSame(0, User::where('email', 'like', 'juan@%')->count());

        // a real one passes, and the 6-digit code is still emailed to prove the inbox is real
        $this->register(['email' => 'juan@gmail.com'])->assertRedirect('/register/verify');
        $this->assertNull(User::where('email', 'juan@gmail.com')->first()->email_verified_at);
    }

    public function test_temporary_email_domains_from_the_downloaded_list_are_blocked(): void
    {
        $this->withBlocklist("abowned.com\nsomeothertemp.net\n", function () {
            $this->register(['email' => 'vafod30785@abowned.com'])
                ->assertSessionHasErrors(['email' => 'This is a temporary (fake) email address. Please use your real email, like Gmail or Yahoo.']);
            $this->assertTrue(RealEmail::isDisposable('inbox.someothertemp.net'));   // sub-domains too
            $this->assertFalse(RealEmail::isDisposable('gmail.com'));
            $this->assertSame(0, User::where('email', 'vafod30785@abowned.com')->count());
        });
    }

    public function test_the_register_page_checks_the_email_while_typing(): void
    {
        $this->withBlocklist("abowned.com\n", function () {
            $this->getJson('/register/check-email?email=vafod30785@abowned.com')
                ->assertOk()->assertJson(['valid' => false, 'message' => 'This is a temporary (fake) email address. Please use your real email, like Gmail or Yahoo.']);
            $this->getJson('/register/check-email?email=juan@gmial.com')->assertJson(['valid' => false]);
            $this->getJson('/register/check-email?email=juan@gmail.com')->assertJson(['valid' => true]);
        });
    }

    public function test_the_blocklist_command_downloads_and_saves_the_lists(): void
    {
        Http::fake([
            'raw.githubusercontent.com/disposable-email-domains/*' => Http::response("abowned.com\nMAILINATOR.com\n# comment\n"),
            'raw.githubusercontent.com/FGRibreau/*' => Http::response("abowned.com\ngmail.com\nnewtemp.org\n"),
        ]);

        $this->withBlocklist('', function () {
            $this->artisan('fmh:update-email-blocklist')->expectsOutputToContain('Saved 3 temporary email domains')->assertExitCode(0);
            // no duplicates, no comments, and real providers like gmail.com are never saved
            $this->assertSame("abowned.com\nmailinator.com\nnewtemp.org\n", File::get(RealEmail::blocklistPath()));
        });
    }

    public function test_register_page_has_the_live_checks(): void
    {
        $this->get('/register')->assertOk()->assertSee('js/animal.js')->assertSee('name="mobile"', false);
        $js = file_get_contents(public_path('js/animal.js'));
        $this->assertStringContainsString('The mobile number must start with 09.', $js);
        $this->assertStringContainsString('Numbers only. Letters, spaces and symbols are not allowed.', $js);
        $this->assertStringContainsString('/register/check-email?email=', $js);
    }
}
