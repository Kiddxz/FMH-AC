<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Creates the 4 roles from the capstone paper and the default permissions of each role.
 * Super Admin can change the permissions later (Phase 8).
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- 1. The 4 roles (capstone REQ002) ----------
        $roles = [
            Role::SUPER_ADMIN => ['Super Admin', 'Manages user accounts, roles and permissions, activity logs, backup and system settings.'],
            Role::VET_ADMIN   => ['Veterinarian/Admin', 'Manages medical records, monitors clinic operations, reviews transactions and reports.'],
            Role::STAFF       => ['Staff/Receptionist', 'Front desk and cashier: walk-ins, appointments, patient flow, inventory, suppliers, POS, waivers.'],
            Role::CUSTOMER    => ['Customer', 'Pet owner: profile, pets, appointments, care instructions and waivers.'],
        ];

        foreach ($roles as $slug => [$name, $description]) {
            Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $description]);
        }

        // ---------- 2. All permissions: slug => [name, module] ----------
        $permissions = [
            'portal.access'             => ['Use the customer portal', 'Customer Portal'],
            'appointments.book'         => ['Book own appointments', 'Appointments'],
            'appointments.view'         => ['View all appointments', 'Appointments'],
            'appointments.manage'       => ['Create, confirm, update and cancel appointments', 'Appointments'],
            'customers.view'            => ['View customer records', 'Customers'],
            'customers.manage'          => ['Register walk-in customers and update customer records', 'Customers'],
            'pets.view'                 => ['View pet profiles', 'Pets'],
            'pets.manage'               => ['Add and update pet profiles', 'Pets'],
            'records.view'              => ['View full medical records', 'Pet Records'],
            'records.write'             => ['Write consultations, prescriptions, treatments and care instructions', 'Pet Records'],
            'records.vaccinations'      => ['View vaccination history', 'Pet Records'],
            'patient_flow.view'         => ['View the patient flow board', 'Patient Flow'],
            'patient_flow.manage'       => ['Check in walk-ins and update patient status', 'Patient Flow'],
            'inventory.view'            => ['View inventory and alerts', 'Inventory'],
            'inventory.manage'          => ['Add items and stock-in deliveries', 'Inventory'],
            'inventory.record_usage'    => ['Record item usage', 'Inventory'],
            'suppliers.manage'          => ['Manage suppliers', 'Inventory'],
            'pos.manage'                => ['Record payments in the POS', 'POS'],
            'transactions.view'         => ['View transaction records', 'POS'],
            'transactions.void'         => ['Void a transaction', 'POS'],
            'waivers.prepare'           => ['Prepare waiver and consent forms', 'Waivers'],
            'waivers.review'            => ['Review signed waivers', 'Waivers'],
            'waivers.view'              => ['View waiver records', 'Waivers'],
            'services.manage'           => ['Manage clinic services and prices', 'Services'],
            'reports.view'              => ['Generate and view reports', 'Reports'],
            'users.manage'              => ['Manage user accounts', 'System'],
            'roles.manage'              => ['Assign roles and permissions', 'System'],
            'activity_logs.view'        => ['View activity logs', 'System'],
            'backups.manage'            => ['Create and restore database backups', 'System'],
            'settings.manage'           => ['Change system settings', 'System'],
        ];

        foreach ($permissions as $slug => [$name, $module]) {
            Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'module' => $module]);
        }

        // ---------- 3. Default permissions per role (from the capstone Scope) ----------
        $defaults = [
            Role::SUPER_ADMIN => [
                'users.manage', 'roles.manage', 'activity_logs.view', 'backups.manage', 'settings.manage',
                // read-only oversight (decision P1): no medical notes, no editing of clinic data
                'appointments.view', 'pets.view', 'transactions.view', 'inventory.view', 'waivers.view', 'reports.view',
            ],
            Role::VET_ADMIN => [
                'appointments.view', 'appointments.manage', 'customers.view', 'pets.view', 'pets.manage',
                'records.view', 'records.write', 'records.vaccinations',
                'patient_flow.view', 'inventory.view', 'inventory.record_usage',
                'transactions.view', 'transactions.void', 'waivers.view', 'waivers.review',
                'services.manage', 'reports.view',
            ],
            Role::STAFF => [
                'appointments.view', 'appointments.manage', 'customers.view', 'customers.manage',
                'pets.view', 'pets.manage', 'records.vaccinations',
                'patient_flow.view', 'patient_flow.manage',
                'inventory.view', 'inventory.manage', 'inventory.record_usage', 'suppliers.manage',
                'pos.manage', 'transactions.view', 'waivers.prepare', 'waivers.view', 'reports.view',
            ],
            Role::CUSTOMER => [
                'portal.access', 'appointments.book',
            ],
        ];

        foreach ($defaults as $roleSlug => $permissionSlugs) {
            $role = Role::where('slug', $roleSlug)->firstOrFail();
            $ids = Permission::whereIn('slug', $permissionSlugs)->pluck('id');
            $role->permissions()->sync($ids);
        }
    }
}
