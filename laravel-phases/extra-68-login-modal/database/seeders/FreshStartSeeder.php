<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Clean start: only what the clinic needs to begin, with NO example customers, pets,
 * appointments, bills or inventory.
 *   php artisan migrate:fresh --seed --seeder=FreshStartSeeder
 *
 * Adds: roles and permissions, services, clinic hours, settings, waiver templates,
 * and the clinic accounts (Super Admin, two Vets, Staff). Pet owners register by themselves.
 * Add the example inventory later if you want it: php artisan db:seed --class=InventorySeeder
 * CHANGE THESE PASSWORDS before the real clinic uses the system.
 */
class FreshStartSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,   // 4 roles + permissions
            ClinicSetupSeeder::class,           // services, clinic hours, settings, waiver templates
        ]);

        $accounts = [
            // email, password, first name, last name, contact, role
            ['superadmin@fmhanimalclinic.com', 'superadmin123', 'System', 'Administrator', '09170000001', Role::SUPER_ADMIN],
            ['admin@fmhanimalclinic.com', 'admin123', 'Maria', 'Santos', '09171234567', Role::VET_ADMIN],
            ['vet2@fmhanimalclinic.com', 'vet12345', 'Jose', 'Ramos', '09171112222', Role::VET_ADMIN],
            ['assistant@fmhanimalclinic.com', 'assistant123', 'Ana', 'Cruz', '09181234567', Role::STAFF],
        ];

        foreach ($accounts as [$email, $password, $first, $last, $contact, $roleSlug]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill([
                'first_name' => $first,
                'last_name' => $last,
                'contact_number' => $contact,
                'password' => $password,   // hashed automatically by the User model
            ]);
            // role, status and verification are set here (trusted code), not from a form
            $user->role_id = Role::where('slug', $roleSlug)->value('id');
            $user->status = 'active';
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
