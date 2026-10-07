<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Stops fake email addresses at registration:
 *  1. temporary / throw-away inboxes (Temp Mail, mailinator, 10minutemail, ...):
 *     the short list below + the big list downloaded by "php artisan fmh:update-email-blocklist"
 *  2. common typos of real providers (gmial.com, yaho.com, ...)
 *  3. a domain that does not exist on the internet (no mail server)
 * The 6-digit code that is emailed afterwards is the final proof that the inbox is real.
 * The register page also asks /register/check-email while typing, so the message shows under the field.
 */
class RealEmail implements ValidationRule
{
    // Real email providers: never treated as temporary
    public const ALWAYS_ALLOWED = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.com.ph', 'ymail.com', 'outlook.com', 'hotmail.com',
        'live.com', 'msn.com', 'icloud.com', 'me.com', 'proton.me', 'protonmail.com', 'aol.com', 'gmx.com', 'zoho.com',
    ];

    // Well-known "throw-away" email services (always blocked, even before the big list is downloaded)
    public const DISPOSABLE = [
        'mailinator.com', '10minutemail.com', '10minutemail.net', 'guerrillamail.com', 'guerrillamail.net',
        'sharklasers.com', 'grr.la', 'yopmail.com', 'yopmail.net', 'temp-mail.org', 'tempmail.com',
        'tempmail.net', 'tempmailo.com', 'tempail.com', 'tmpmail.org', 'tmpmail.net', 'trashmail.com',
        'trashmail.de', 'getnada.com', 'nada.email', 'dispostable.com', 'maildrop.cc', 'mintemail.com',
        'mohmal.com', 'fakeinbox.com', 'fakemail.net', 'throwawaymail.com', 'emailondeck.com',
        'mailnesia.com', 'mailcatch.com', 'spamgourmet.com', 'moakt.com', 'emailfake.com',
        'burnermail.io', 'mail.tm', 'mailpoof.com', 'inboxkitten.com', 'byom.de', 'dropmail.me',
        'tempinbox.com', 'mytemp.email', 'spam4.me', 'discard.email', 'luxusmail.org', 'cuvox.de',
    ];

    // Typo => the real provider (for the "Did you mean ...?" message)
    public const TYPOS = [
        'gmial.com' => 'gmail.com', 'gmal.com' => 'gmail.com', 'gamil.com' => 'gmail.com', 'gmaill.com' => 'gmail.com',
        'gmai.com' => 'gmail.com', 'gnail.com' => 'gmail.com', 'gmail.co' => 'gmail.com', 'gmail.con' => 'gmail.com',
        'gmail.cm' => 'gmail.com', 'gmail.om' => 'gmail.com', 'gmail.ph' => 'gmail.com', 'gmailcom' => 'gmail.com',
        'yaho.com' => 'yahoo.com', 'yahooo.com' => 'yahoo.com', 'yahoo.co' => 'yahoo.com', 'yahoo.con' => 'yahoo.com',
        'hotmial.com' => 'hotmail.com', 'hotmal.com' => 'hotmail.com', 'hotmail.co' => 'hotmail.com', 'hotmail.con' => 'hotmail.com',
        'outlok.com' => 'outlook.com', 'outloo.com' => 'outlook.com', 'outlook.co' => 'outlook.com', 'outlook.con' => 'outlook.com',
        'iclod.com' => 'icloud.com', 'icloud.co' => 'icloud.com',
    ];

    // Tests may set this to fake the internet check: fn (string $domain): bool
    // (while the automatic tests run, the internet check is skipped unless this is set)
    public static ?Closure $domainCheck = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($problem = self::problem((string) $value)) {
            $fail($problem);
        }
    }

    // What is wrong with the email address, or null when it looks real
    public static function problem(string $email): ?string
    {
        $email = strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! str_contains($email, '@')) {
            return 'Enter a valid email address (e.g. juan@gmail.com).';
        }

        $domain = substr(strrchr($email, '@'), 1);

        if (isset(self::TYPOS[$domain])) {
            return 'Did you mean ' . strstr($email, '@', true) . '@' . self::TYPOS[$domain] . '? Please check your email address.';
        }

        if (self::isDisposable($domain)) {
            return 'This is a temporary (fake) email address. Please use your real email, like Gmail or Yahoo.';
        }

        if (! self::domainCanReceiveMail($domain)) {
            return 'This email address does not exist. Please check the part after the @.';
        }

        return null;
    }

    // Temporary email domain? Also catches sub-domains, e.g. "x.mailinator.com".
    public static function isDisposable(string $domain): bool
    {
        if (in_array($domain, self::ALWAYS_ALLOWED, true)) {
            return false;
        }

        $blocked = self::blocklist();
        $parts = explode('.', $domain);
        while (count($parts) >= 2) {
            if (isset($blocked[implode('.', $parts)])) {
                return true;
            }
            array_shift($parts);
        }

        return false;
    }

    // The file saved by "php artisan fmh:update-email-blocklist"
    public static function blocklistPath(): string
    {
        return storage_path('app/disposable_email_domains.txt');
    }

    // All blocked domains as [domain => true] (read once per request)
    private static ?array $blocklist = null;

    private static function blocklist(): array
    {
        if (self::$blocklist === null) {
            self::$blocklist = array_fill_keys(self::DISPOSABLE, true);
            if (is_file(self::blocklistPath())) {
                foreach (file(self::blocklistPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $domain) {
                    self::$blocklist[$domain] = true;
                }
            }
        }

        return self::$blocklist;
    }

    // Tests: forget the list that was read, so a new file is used
    public static function forgetBlocklist(): void
    {
        self::$blocklist = null;
    }

    // A real email domain has a mail server (MX record) or at least an address (A record)
    public static function domainCanReceiveMail(string $domain): bool
    {
        if (self::$domainCheck) {
            return (bool) (self::$domainCheck)($domain);
        }
        if (app()->runningUnitTests()) {
            return true;
        }

        return checkdnsrr($domain . '.', 'MX') || checkdnsrr($domain . '.', 'A');
    }
}
