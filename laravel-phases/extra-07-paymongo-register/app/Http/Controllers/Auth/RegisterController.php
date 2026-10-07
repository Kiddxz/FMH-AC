<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Rules\RealEmail;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Customer self-registration with a 6-digit email code (capstone Fig 6.3).
 * Only CUSTOMER accounts can register here. Staff, Vet/Admin and Super Admin
 * accounts are created by the Super Admin (decision P6).
 */
class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    // Step 1: save the account (not yet verified) and email a code
    public function store(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            // Taken only by VERIFIED accounts. An unfinished registration can be started again.
            // RealEmail: no throw-away inboxes, typos like "gmial.com" or domains that do not exist
            'email' => ['bail', 'required', 'email', 'max:255', new RealEmail(), Rule::unique('users', 'email')->whereNotNull('email_verified_at')],
            // Checked one by one so the message says exactly what is wrong
            'mobile' => ['bail', 'required', 'regex:/^[0-9]+$/', 'starts_with:09', 'digits:11'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'tnc' => ['accepted'],
        ], [
            'email.unique' => 'This email is already registered. Please log in instead.',
            'mobile.regex' => 'The mobile number must contain numbers only (no letters, spaces or symbols).',
            'mobile.starts_with' => 'The mobile number must start with 09 (e.g. 09171234567).',
            'mobile.digits' => 'The mobile number must be exactly 11 digits (e.g. 09171234567).',
            'password.confirmed' => 'The passwords do not match.',
            'tnc.accepted' => 'You must agree to the Terms & Conditions.',
        ]);

        $user = User::withTrashed()->firstOrNew(['email' => $data['email']]);
        if ($user->trashed()) {
            return back()->withInput()->withErrors(['email' => 'This email cannot be used. Please contact FMH Animal Clinic.']);
        }

        $user->fill([
            'first_name' => $data['firstname'],
            'last_name' => $data['lastname'],
            'contact_number' => $data['mobile'],
            'password' => $data['password'],
        ]);
        // Set by trusted code only: a new self-registered account is always a Customer
        $user->role_id = Role::where('slug', Role::CUSTOMER)->value('id');
        $user->status = 'active';
        $user->email_verified_at = null;
        $user->save();

        $codes->send($user->email, 'register');
        $request->session()->put('verify_email', $user->email);

        return redirect()->route('register.verify')
            ->with('status', 'We sent a 6-digit code to ' . $user->email . '. Enter it below to finish your registration.');
    }

    // Step 2: the "enter your code" page
    public function showVerify(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('verify_email')) {
            return redirect()->route('register');
        }

        return view('auth.verify-email', ['email' => $request->session()->get('verify_email')]);
    }

    // Step 3: check the code, activate the account and log in
    public function verify(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $email = $request->session()->get('verify_email');
        if (! $email) {
            return redirect()->route('register');
        }

        $request->validate(['code' => ['required', 'digits:6']]);

        $record = $codes->check($email, 'register', $request->input('code'));

        $user = User::where('email', $email)->firstOrFail();

        DB::transaction(function () use ($codes, $record, $user) {
            $codes->markUsed($record);

            $user->forceFill(['email_verified_at' => now()])->save();

            // Give the account its customer (pet owner) profile.
            // If the clinic already registered this person as a walk-in with the same email,
            // that record is linked, so their pets and history appear in the portal.
            if ($user->hasRole(Role::CUSTOMER) && ! $user->customer) {
                $customer = Customer::whereNull('user_id')->where('email', $user->email)->first()
                    ?? new Customer([
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'contact_number' => $user->contact_number,
                        'email' => $user->email,
                        'is_walk_in' => false,
                    ]);
                $customer->user_id = $user->id;
                $customer->save();
            }
        });

        $request->session()->forget('verify_email');

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('registered', 'Authentication', $user->full_name . ' verified their email and created an account.', $user);

        return redirect($user->homeUrl())->with('phase_notice', 'Welcome to FMH Animal Clinic, ' . $user->first_name . '! Your account is ready.');
    }

    // "Send a new code" link on the verify page
    public function resend(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $email = $request->session()->get('verify_email');
        if (! $email) {
            return redirect()->route('register');
        }

        $codes->send($email, 'register');

        return back()->with('status', 'A new code was sent to ' . $email . '.');
    }
}
