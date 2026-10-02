<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts, one per role, plus a second veterinarian.
 * Passwords are hashed automatically by the User model ('password' => 'hashed').
 * CHANGE THESE PASSWORDS before the system is used by the real clinic.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // email, password, first name, last name, contact, role
            ['superadmin@fmhanimalclinic.com', 'superadmin123', 'System', 'Administrator', '09170000001', Role::SUPER_ADMIN],
            ['admin@fmhanimalclinic.com', 'admin123', 'Maria', 'Santos', '09171234567', Role::VET_ADMIN],
            ['vet2@fmhanimalclinic.com', 'vet12345', 'Jose', 'Ramos', '09171112222', Role::VET_ADMIN],
            ['assistant@fmhanimalclinic.com', 'assistant123', 'Ana', 'Cruz', '09181234567', Role::STAFF],
            ['owner@fmhanimalclinic.com', 'owner123', 'Mark', 'Santos', '09171234560', Role::CUSTOMER],
        ];

        foreach ($accounts as [$email, $password, $first, $last, $contact, $roleSlug]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill([
                'first_name' => $first,
                'last_name' => $last,
                'contact_number' => $contact,
                'password' => $password,
            ]);
            // role, status and verification are set here (trusted code), not from a form
            $user->role_id = Role::where('slug', $roleSlug)->value('id');
            $user->status = 'active';
            $user->email_verified_at = now();
            $user->save();
        }

        // The customer account also needs a customer (pet owner) profile
        $owner = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $customer = Customer::firstOrNew(['user_id' => $owner->id]);
        $customer->fill([
            'first_name' => $owner->first_name,
            'last_name' => $owner->last_name,
            'contact_number' => $owner->contact_number,
            'email' => $owner->email,
            'address' => 'Las Piñas City',
            'is_walk_in' => false,
        ]);
        $customer->user_id = $owner->id;
        $customer->save();
    }
}
