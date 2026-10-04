<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * One login page for all 4 roles (decision P15). Capstone Fig 6.1 / FR-REQ001.
 */
class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;   // wrong tries allowed per minute (per email + computer)

    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // ---------- 1. Stop password guessing ----------
        $throttleKey = Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->logFailure('login_throttled', 'Too many login attempts for ' . $credentials['email'] . '. Blocked for ' . $seconds . ' seconds.', $credentials['email']);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // ---------- 2. Check email and password ----------
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            // Phase 16: wrong tries are logged (never the password itself)
            $this->logFailure('login_failed', 'Failed login for ' . $credentials['email'] . ($user ? ' (wrong password).' : ' (no account with this email).'), $credentials['email']);
            throw ValidationException::withMessages([
                'email' => 'Invalid email or password.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // ---------- 3. Account must be active (Super Admin can deactivate accounts) ----------
        if (! $user->isActive()) {
            $this->logFailure('login_blocked', 'Login refused: the account of ' . $user->full_name . ' is inactive.', $user->email);
            throw ValidationException::withMessages([
                'email' => 'Your account is inactive. Please contact FMH Animal Clinic.',
            ]);
        }

        // ---------- 4. Email must be verified first ----------
        if (! $user->email_verified_at) {
            $codes->send($user->email, 'register');
            $request->session()->put('verify_email', $user->email);

            return redirect()->route('register.verify')
                ->with('status', 'Please verify your email first. We sent a new 6-digit code to ' . $user->email . '.');
        }

        // ---------- 5. Log in ----------
        Auth::login($user, $request->boolean('rememberme'));
        $request->session()->regenerate();   // new session id: protects against session fixation

        $user->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('login', 'Authentication', $user->full_name . ' logged in.', $user);

        // Go back to the page the user wanted before logging in, but only if it is inside
        // their own area (e.g. a Staff user is never sent to a /portal page). Otherwise: their dashboard.
        $home = $user->homeUrl();
        $intended = $request->session()->pull('url.intended');

        return redirect(is_string($intended) && str_starts_with($intended, $home) ? $intended : $home);
    }

    // A failed login is written to the activity log. If the email belongs to an account,
    // the entry is linked to that account so the Super Admin can see who was targeted.
    private function logFailure(string $action, string $description, string $email): void
    {
        $user = User::where('email', $email)->first();
        ActivityLog::record($action, 'Authentication', $description, $user, $user?->id);
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            ActivityLog::record('logout', 'Authentication', $user->full_name . ' logged out.', $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }
}
