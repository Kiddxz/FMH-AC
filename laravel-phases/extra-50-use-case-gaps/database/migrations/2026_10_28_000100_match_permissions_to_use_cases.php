<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Permissions that follow the use case diagrams of the paper (Figures 5.1 - 5.3, 6.1):
 *  - "View and edit Digital Liability Waiver and Consent Forms": the Vet/Admin and the Super Admin edit the
 *    waiver form templates (new permission waiver_templates.manage)
 *  - Sales and Transaction Records are read-only for the Vet/Admin (Figure 6.1): voiding a wrong bill
 *    moves to the Staff/Cashier who runs the POS
 * Runs on an existing database too (the seeder does the same for a new one).
 */
return new class extends Migration
{
    public function up(): void
    {
        $roles = Role::whereIn('slug', [Role::SUPER_ADMIN, Role::VET_ADMIN, Role::STAFF])->get()->keyBy('slug');
        if ($roles->isEmpty()) {
            return;   // a new database: the seeder adds everything
        }

        $templates = Permission::firstOrCreate(['slug' => 'waiver_templates.manage'], ['name' => 'Edit waiver and consent form templates', 'module' => 'Waivers']);
        $roles->get(Role::VET_ADMIN)?->permissions()->syncWithoutDetaching([$templates->id]);
        $roles->get(Role::SUPER_ADMIN)?->permissions()->syncWithoutDetaching([$templates->id]);

        $void = Permission::where('slug', 'transactions.void')->first();
        if ($void) {
            $roles->get(Role::VET_ADMIN)?->permissions()->detach($void->id);
            $roles->get(Role::STAFF)?->permissions()->syncWithoutDetaching([$void->id]);
        }
    }

    public function down(): void
    {
        $roles = Role::whereIn('slug', [Role::VET_ADMIN, Role::STAFF])->get()->keyBy('slug');
        $void = Permission::where('slug', 'transactions.void')->first();
        if ($void) {
            $roles->get(Role::STAFF)?->permissions()->detach($void->id);
            $roles->get(Role::VET_ADMIN)?->permissions()->syncWithoutDetaching([$void->id]);
        }
        Permission::where('slug', 'waiver_templates.manage')->first()?->delete();
    }
};
