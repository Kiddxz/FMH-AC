<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Runs all seeders in the correct order:
 *   php artisan db:seed            (fill the database)
 *   php artisan migrate:fresh --seed   (rebuild all tables AND fill them)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,   // 4 roles + permissions
            UserSeeder::class,                  // demo accounts (needs roles)
            ClinicSetupSeeder::class,           // services, clinic hours, settings, waiver templates
            InventorySeeder::class,             // suppliers, items, batches, usage log (needs users)
            DemoDataSeeder::class,              // fictitious customers, pets, appointments, one record
        ]);
    }
}
