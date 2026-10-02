<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The email that carries the 6-digit code.
 * During development (MAIL_MAILER=log) it is written to storage/logs/laravel.log instead of being sent.
 */
class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose,   // "register" or "password_reset"
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === 'register'
                ? 'FMH Animal Clinic - Verify your email'
                : 'FMH Animal Clinic - Password reset code',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.verification-code');
    }
}
