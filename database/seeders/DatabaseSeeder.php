<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BureauSeeder::class,
            WorkflowEtapesSeeder::class,
            RolesAndPermissionsSeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (app()->environment(['local', 'development'])) {
            $this->call([
                LocalUserSeeder::class,
                TestUsersSeeder::class,
                TestEnvironmentSeeder::class,
            ]);
        }
    }
}
