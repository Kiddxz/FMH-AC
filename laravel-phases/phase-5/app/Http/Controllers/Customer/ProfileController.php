<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Customer profile: view, edit details, change password (capstone FR-REQ003).
 * The email cannot be changed here because it is the login name that was verified.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('customer.profile', ['user' => $request->user(), 'customer' => $request->user()->customer]);
    }

    public function edit(Request $request): View
    {
        return view('customer.profile-edit', ['user' => $request->user(), 'customer' => $request->user()->customer]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [
            'mobile.regex' => 'Enter an 11-digit mobile number that starts with 09 (e.g. 09171234567).',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $data) {
            $user->update([
                'first_name' => $data['firstname'],
                'last_name' => $data['lastname'],
                'contact_number' => $data['mobile'],
            ]);

            // Keep the pet owner record (used by the clinic staff) the same as the account
            $user->customer?->update([
                'first_name' => $data['firstname'],
                'last_name' => $data['lastname'],
                'contact_number' => $data['mobile'],
                'address' => $data['address'] ?? null,
            ]);
        });

        ActivityLog::record('updated', 'Profile', $user->full_name . ' updated their profile.', $user);

        return redirect()->route('portal.profile')->with('status', 'Your profile was updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different' => 'Your new password must be different from your current password.',
            'password.confirmed' => 'The new passwords do not match.',
        ], [
            'password' => 'new password',
        ]);

        $user = $request->user();
        $user->password = $request->input('password');   // hashed automatically
        $user->save();

        // Log out the account on every other computer or phone
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        ActivityLog::record('password_changed', 'Profile', $user->full_name . ' changed their password.', $user);

        return redirect()->route('portal.profile')->with('status', 'Your password was changed.');
    }
}
