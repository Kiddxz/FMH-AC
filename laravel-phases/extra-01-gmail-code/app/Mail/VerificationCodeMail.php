<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The email that carries the 6-digit code (a designed HTML version plus a plain-text version).
 * With MAIL_MAILER=smtp (Gmail) it is really sent; with MAIL_MAILER=log it is written to storage/logs/laravel.log.
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
        return new Content(
            view: 'emails.verification-code-html',
            text: 'emails.verification-code',
        );
    }
}
