<?php

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Creates, emails and checks the 6-digit codes used for
 * registration (capstone Fig 6.3) and password reset (Fig 6.4).
 */
class VerificationCodeService
{
    public const EXPIRES_MINUTES = 10;   // a code works for 10 minutes
    public const MAX_ATTEMPTS = 5;       // after 5 wrong tries the code is blocked

    // Make a new code, save it HASHED, and email it. Older unused codes stop working.
    public function send(string $email, string $purpose): void
    {
        VerificationCode::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        $record = VerificationCode::create([
            'email' => $email,
            'code' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
        ]);

        try {
            Mail::to($email)->send(new VerificationCodeMail($code, $purpose));
        } catch (TransportExceptionInterface $e) {
            // Gmail could not be reached (no internet, wrong app password, daily limit...).
            // The code was never received, so remove it and show a clear message instead of an error page.
            $record->delete();
            Log::error('Verification code email to ' . $email . ' failed: ' . $e->getMessage());

            throw ValidationException::withMessages([
                'email' => 'We could not send the code to ' . $email . ' right now. Please check the email address and your internet connection, then try again.',
            ]);
        }
    }

    // Check a typed code. Returns the code record when correct; otherwise shows an error under the "code" field.
    public function check(string $email, string $purpose, string $code): VerificationCode
    {
        $record = VerificationCode::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $record) {
            $this->fail('No active code found. Please request a new code.');
        }

        if ($record->isExpired()) {
            $this->fail('This code has expired. Please request a new code.');
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $this->fail('Too many wrong attempts. Please request a new code.');
        }

        if (! Hash::check($code, $record->code)) {
            $record->increment('attempts');
            $this->fail('The code you entered is incorrect.');
        }

        return $record;
    }

    // A code can only be used once
    public function markUsed(VerificationCode $record): void
    {
        $record->forceFill(['used_at' => now()])->save();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['code' => $message]);
    }
}
