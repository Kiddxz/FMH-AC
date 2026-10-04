{{-- The designed (HTML) version of the code email. Email programs like Gmail ignore <style> blocks
     and outside CSS files, so every style is written inside the tags. Plain-text version: verification-code.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic</title>
</head>
<body style="margin: 0; padding: 0; background: #f7f3ed; font-family: Arial, Helvetica, sans-serif; color: #333333;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f7f3ed; padding: 30px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 480px; background: #ffffff; border-radius: 14px; border: 1px solid #eee6db;">
          <tr>
            <td style="padding: 26px 30px 10px; text-align: center; font-size: 22px; font-weight: bold; color: #26364a;">
              🐾 {{ \App\Models\Setting::get('clinic_name') }}
            </td>
          </tr>
          <tr>
            <td style="padding: 6px 30px 0; text-align: center; font-size: 15px; line-height: 1.6; color: #475569;">
              @if ($purpose === 'register')
                Thank you for creating an account. Use this code to verify your email address:
              @else
                We received a request to reset your password. Use this code to set a new password:
              @endif
            </td>
          </tr>
          <tr>
            <td align="center" style="padding: 22px 30px;">
              <div style="display: inline-block; padding: 14px 26px; border-radius: 12px; background: #fff1df; border: 1px dashed #e89427; font-size: 34px; font-weight: bold; letter-spacing: 8px; color: #b2610c;">{{ $code }}</div>
            </td>
          </tr>
          <tr>
            <td style="padding: 0 30px 24px; text-align: center; font-size: 13px; line-height: 1.6; color: #64748b;">
              This code expires in <strong>{{ \App\Services\VerificationCodeService::EXPIRES_MINUTES }} minutes</strong> and can only be used once.<br>
              Never share this code with anyone. Clinic staff will never ask for it.<br>
              If you did not request this, you can ignore this email.
            </td>
          </tr>
          <tr>
            <td style="padding: 14px 30px; border-top: 1px solid #eee6db; text-align: center; font-size: 12px; color: #94a3b8;">
              {{ \App\Models\Setting::get('clinic_name') }} · {{ \App\Models\Setting::get('clinic_address') }} · {{ \App\Models\Setting::get('clinic_contact') }}
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
