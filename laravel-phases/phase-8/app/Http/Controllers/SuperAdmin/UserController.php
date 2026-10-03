<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * User account management, Super Admin only (capstone FR-REQ004, decision P6):
 * create, edit, change role, activate / deactivate, set a new password.
 * Accounts are deactivated instead of deleted, so their records stay complete.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $role = $request->query('role');
        $status = $request->query('status');

        $users = User::with('role')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($role, fn ($q) => $q->whereHas('role', fn ($r) => $r->where('slug', $role)))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($q) => $q->where('status', $status))
            ->orderBy('role_id')->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('id')->get(),
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('superadmin.users.form', ['user' => new User(), 'roles' => Role::orderBy('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);

        $user = DB::transaction(function () use ($data) {
            $user = new User([
                'first_name' => $data['firstname'],
                'last_name' => $data['lastname'],
                'email' => $data['email'],
                'contact_number' => $data['mobile'],
                'password' => $data['password'],
            ]);
            $user->role_id = $data['role_id'];
            $user->status = 'active';
            $user->email_verified_at = now();   // created by the Super Admin, so no email code is needed
            $user->save();

            $this->ensureCustomerProfile($user);

            return $user;
        });

        ActivityLog::record('created', 'User Accounts', 'Created ' . $user->role->name . ' account for ' . $user->full_name . ' (' . $user->email . ').', $user);

        return redirect()->route('superadmin.users.index')->with('status', 'The account of ' . $user->full_name . ' was created.');
    }

    public function edit(User $user): View
    {
        return view('superadmin.users.form', ['user' => $user, 'roles' => Role::orderBy('id')->get()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateUser($request, $user);
        $oldRole = $user->role?->name;

        // The Super Admin cannot remove their own Super Admin role (they would lock themselves out)
        if ($user->is($request->user()) && (int) $data['role_id'] !== $user->role_id) {
            return back()->withInput()->withErrors(['role_id' => 'You cannot change your own role.']);
        }

        DB::transaction(function () use ($user, $data) {
            $user->fill([
                'first_name' => $data['firstname'],
                'last_name' => $data['lastname'],
                'email' => $data['email'],
                'contact_number' => $data['mobile'],
            ]);
            $user->role_id = $data['role_id'];
            if (! empty($data['password'])) {
                $user->password = $data['password'];
                $user->setRememberToken(Str::random(60));
            }
            $user->save();

            // Keep the linked pet owner record the same
            $user->customer?->update([
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'contact_number' => $user->contact_number,
                'email' => $user->email,
            ]);
            $this->ensureCustomerProfile($user->fresh('role'));
        });

        $user->load('role');
        $note = $oldRole !== $user->role->name ? ' Role changed from ' . $oldRole . ' to ' . $user->role->name . '.' : '';
        $note .= ! empty($data['password']) ? ' Password was reset.' : '';
        ActivityLog::record('updated', 'User Accounts', 'Updated account of ' . $user->full_name . '.' . $note, $user);

        return redirect()->route('superadmin.users.index')->with('status', 'The account of ' . $user->full_name . ' was updated.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }

        $user->status = $user->isActive() ? 'inactive' : 'active';
        $user->save();

        if (! $user->isActive()) {
            // Log the person out everywhere right away
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        ActivityLog::record($user->isActive() ? 'activated' : 'deactivated', 'User Accounts',
            ucfirst($user->isActive() ? 'activated' : 'deactivated') . ' the account of ' . $user->full_name . '.', $user);

        return back()->with('status', 'The account of ' . $user->full_name . ' is now ' . $user->status . '.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'role_id' => ['required', 'exists:roles,id'],
            // Required for a new account; optional when editing (fill it only to set a new password)
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'email.unique' => 'This email is already used by another account.',
            'mobile.regex' => 'Enter an 11-digit mobile number that starts with 09 (e.g. 09171234567).',
            'password.confirmed' => 'The passwords do not match.',
        ], [
            'role_id' => 'role',
        ]);
    }

    // A Customer account always needs its pet owner profile
    private function ensureCustomerProfile(User $user): void
    {
        if (! $user->hasRole(Role::CUSTOMER) || $user->customer()->exists()) {
            return;
        }

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
}
