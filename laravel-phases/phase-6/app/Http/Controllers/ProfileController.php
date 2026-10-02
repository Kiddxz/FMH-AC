<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * "My Profile" for clinic accounts: Staff (Phase 6), Vet/Admin (Phase 7) and Super Admin (Phase 8).
 * Each role uses its own pages: staff/profile.blade.php, admin/profile.blade.php, ...
 * (Customers have their own profile controller because they also have an address.)
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view($this->area($request) . '.profile', ['user' => $request->user()]);
    }

    public function edit(Request $request): View
    {
        return view($this->area($request) . '.profile-edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
        ], [
            'mobile.regex' => 'Enter an 11-digit mobile number that starts with 09 (e.g. 09171234567).',
        ]);

        $user = $request->user();
        $user->update([
            'first_name' => $data['firstname'],
            'last_name' => $data['lastname'],
            'contact_number' => $data['mobile'],
        ]);

        ActivityLog::record('updated', 'Profile', $user->full_name . ' updated their profile.', $user);

        return redirect()->route($this->area($request) . '.profile')->with('status', 'Your profile was updated.');
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

        return redirect()->route($this->area($request) . '.profile')->with('status', 'Your password was changed.');
    }

    // Which folder of pages belongs to the logged-in role
    private function area(Request $request): string
    {
        return match ($request->user()->role?->slug) {
            Role::STAFF => 'staff',
            Role::VET_ADMIN => 'admin',
            Role::SUPER_ADMIN => 'superadmin',
            default => abort(403),
        };
    }
}
