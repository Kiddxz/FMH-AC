<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\Setting;
use App\Models\User;
use App\Models\VerificationCode;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The 6-digit code email (Gmail) used to prove that an email address is real and owned by the person.
 */
class EmailCodeTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'firstname' => 'Liza', 'lastname' => 'Soberano', 'email' => 'liza@example.com', 'mobile' => '09171231234',
        'password' => 'petlover1', 'password_confirmation' => 'petlover1', 'tnc' => '1',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_the_code_email_has_a_designed_html_and_a_plain_text_version(): void
    {
        Setting::set('clinic_name', 'FMH Animal Clinic Pamplona');
        $mail = new VerificationCodeMail('482913', 'register');

        $mail->assertSeeInHtml('482913');
        $mail->assertSeeInHtml('FMH Animal Clinic Pamplona');
        $mail->assertSeeInHtml('verify your email address');
        $mail->assertSeeInText('482913');
        $this->assertSame('FMH Animal Clinic - Verify your email', $mail->envelope()->subject);

        (new VerificationCodeMail('111222', 'password_reset'))->assertSeeInHtml('reset your password');
    }

    public function test_when_gmail_cannot_be_reached_the_user_sees_a_clear_message(): void
    {
        // A mail server that does not exist: sending fails like Gmail would with no internet
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);

        $this->from('/register')->post('/register', $this->form)
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['email' => 'We could not send the code to liza@example.com right now. Please check the email address and your internet connection, then try again.']);

        // no code that nobody received, and the account is not verified (it cannot log in)
        $this->assertSame(0, VerificationCode::where('email', 'liza@example.com')->count());
        $this->assertNull(User::where('email', 'liza@example.com')->value('email_verified_at'));

        // the person can simply try again once the email works
        config(['mail.default' => 'array']);
        $this->post('/register', $this->form)->assertRedirect('/register/verify');
        $this->assertSame(1, VerificationCode::where('email', 'liza@example.com')->count());
    }

    public function test_an_unverified_email_cannot_log_in(): void
    {
        config(['mail.default' => 'array']);
        $this->post('/register', $this->form);

        $this->post('/login', ['email' => 'liza@example.com', 'password' => 'petlover1']);
        $this->assertGuest();
    }

    public function test_the_test_email_command(): void
    {
        config(['mail.default' => 'array']);
        $this->artisan('fmh:test-email', ['to' => 'clinic@example.com'])
            ->expectsOutputToContain('Test email sent to clinic@example.com')
            ->assertExitCode(0);

        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);
        $this->artisan('fmh:test-email', ['to' => 'clinic@example.com'])
            ->expectsOutputToContain('Sending failed')
            ->assertExitCode(1);
    }
}
