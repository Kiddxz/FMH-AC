<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Stops fake email addresses at registration:
 *  1. throw-away inboxes (mailinator, 10minutemail, ...)
 *  2. common typos of real providers (gmial.com, yaho.com, ...)
 *  3. a domain that does not exist on the internet (no mail server)
 * The 6-digit code that is emailed afterwards is the final proof that the inbox is real.
 */
class RealEmail implements ValidationRule
{
    // Free "throw-away" email services
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
        $email = strtolower(trim((string) $value));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! str_contains($email, '@')) {
            $fail('Enter a valid email address (e.g. juan@gmail.com).');

            return;
        }

        $domain = substr(strrchr($email, '@'), 1);

        if (isset(self::TYPOS[$domain])) {
            $fail('Did you mean ' . strstr($email, '@', true) . '@' . self::TYPOS[$domain] . '? Please check your email address.');

            return;
        }

        if (in_array($domain, self::DISPOSABLE, true)) {
            $fail('Temporary or throw-away email addresses are not allowed. Please use your real email.');

            return;
        }

        if (! self::domainCanReceiveMail($domain)) {
            $fail('This email address does not exist. Please check the part after the @.');
        }
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
