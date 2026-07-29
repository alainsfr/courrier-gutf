<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Référentiels workflow (CCT) — toujours
        $this->call([
            BureauSeeder::class,
            WorkflowEtapesSeeder::class,
        ]);

        if (app()->environment(['local', 'development'])) {
            $this->call([
                RolesAndPermissionsSeeder::class,
                SuperAdminSeeder::class,
                LocalUserSeeder::class,
                TestUsersSeeder::class,
                TestEnvironmentSeeder::class,
            ]);
        }
    }
}
