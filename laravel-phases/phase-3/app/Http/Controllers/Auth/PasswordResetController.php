<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
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
 * The new password must be different from the old one.
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

        if ($user && $user->isActive() && $user->email_verified_at) {
            $codes->send($user->email, 'password_reset');
        }

        $request->session()->put('reset_email', $data['email']);

        // Same message whether or not the email exists, so strangers cannot check who has an account
        return redirect()->route('password.reset')
            ->with('status', 'If ' . $data['email'] . ' is registered, a 6-digit code was sent to it.');
    }

    // Step 2: the "enter code and new password" page
    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', ['email' => $request->session()->get('reset_email')]);
    }

    // Step 3: check the code and save the new password
    public function reset(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $email = $request->session()->get('reset_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'The passwords do not match.',
        ]);

        // The code is checked FIRST, so nobody can test passwords without a valid code
        $record = $codes->check($email, 'password_reset', $data['code']);

        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['code' => 'The code you entered is incorrect.']);
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

        $request->session()->forget('reset_email');

        return redirect()->route('login')->with('status', 'Your password was changed. You can now log in with your new password.');
    }
}
