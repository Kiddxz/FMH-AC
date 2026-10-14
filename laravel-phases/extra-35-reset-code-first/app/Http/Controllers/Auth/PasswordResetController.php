<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Forgot password: email -> 6-digit code -> new password (capstone Fig 6.4).
 *  1. The email must belong to an account (otherwise an error is shown, as the clinic asked).
 *  2. The code is confirmed first; only then the new password fields are shown.
 *  3. The new password must be different from the old one.
 * Sending codes and checking them have request limits (throttle), so nobody can try many emails or codes.
 */
class PasswordResetController extends Controller
{
    public function show(): View
    {
        return view('auth.forgot-password');
    }

    // Step 1: send a code (only to an existing, active, verified account)
    public function sendCode(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages(['email' => 'No account is registered with this email address. Please check it, or create an account.']);
        }
        if (! $user->isActive()) {
            throw ValidationException::withMessages(['email' => 'This account is deactivated. Please contact the clinic.']);
        }
        if (! $user->email_verified_at) {
            throw ValidationException::withMessages(['email' => 'This email is not verified yet. Please finish your registration first.']);
        }

        $codes->send($user->email, 'password_reset');

        $request->session()->put('reset_email', $user->email);
        $request->session()->forget('reset_verified');   // a new code must be confirmed again

        return redirect()->route('password.reset')
            ->with('status', 'A 6-digit code was sent to ' . $user->email . '.');
    }

    // The reset page: first the code only; after the code is confirmed, the new password fields
    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', [
            'email' => $request->session()->get('reset_email'),
            'verified' => $this->verifiedCode($request) !== null,
        ]);
    }

    // Step 2: confirm the code. Only then the new password fields are shown.
    public function checkCode(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $email = $request->session()->get('reset_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        $data = $request->validate(['code' => ['required', 'digits:6']], [
            'code.digits' => 'The code must be exactly 6 digits.',
        ]);

        $record = $codes->check($email, 'password_reset', $data['code']);   // wrong / expired code: error under the code

        // Remember (on the server) which code was confirmed, for 10 minutes
        $request->session()->put('reset_verified', [
            'email' => $email,
            'record' => $record->id,
            'until' => now()->addMinutes(VerificationCodeService::EXPIRES_MINUTES)->timestamp,
        ]);

        return redirect()->route('password.reset')->with('status', 'Code confirmed. Now choose your new password.');
    }

    // Step 3: save the new password (only after the code was confirmed)
    public function reset(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $email = $request->session()->get('reset_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        $record = $this->verifiedCode($request);
        if (! $record) {
            $request->session()->forget('reset_verified');

            return redirect()->route('password.reset')
                ->withErrors(['code' => 'Please confirm your code first. If it has expired, request a new code.']);
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'The passwords do not match.',
        ]);

        $user = User::where('email', $email)->first();
        if (! $user) {
            return redirect()->route('password.request');
        }

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Your new password must be different from your old password.',
            ]);
        }

        DB::transaction(function () use ($codes, $record, $user, $data) {
            $codes->markUsed($record);

            $user->password = $data['password'];              // hashed automatically by the User model
            $user->setRememberToken(Str::random(60));         // "Remember me" on other devices stops working
            $user->save();

            // Log out every other place where this account is logged in
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'password_reset',
            'module' => 'Authentication',
            'description' => $user->full_name . ' reset their password using an email code.',
            'subject_type' => $user->getMorphClass(),
            'subject_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->session()->forget(['reset_email', 'reset_verified']);

        return redirect()->route('login')->with('status', 'Your password was changed. You can now log in with your new password.');
    }

    // The confirmed code of this session, if it is still valid (same email, not used, not too old)
    private function verifiedCode(Request $request): ?VerificationCode
    {
        $verified = $request->session()->get('reset_verified');
        if (! is_array($verified) || ($verified['email'] ?? null) !== $request->session()->get('reset_email')
            || ($verified['until'] ?? 0) < now()->timestamp) {
            return null;
        }

        return VerificationCode::where('id', $verified['record'] ?? 0)->where('email', $verified['email'])
            ->where('purpose', 'password_reset')->whereNull('used_at')->first();
    }
}
