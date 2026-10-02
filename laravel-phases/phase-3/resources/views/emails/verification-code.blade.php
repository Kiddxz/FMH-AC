FMH Animal Clinic

@if ($purpose === 'register')
Thank you for creating an account. Use this code to verify your email address:
@else
We received a request to reset your password. Use this code to set a new password:
@endif

    {{ $code }}

This code expires in {{ \App\Services\VerificationCodeService::EXPIRES_MINUTES }} minutes and can only be used once.
If you did not request this, you can ignore this email.

FMH Animal Clinic - Las Piñas City
