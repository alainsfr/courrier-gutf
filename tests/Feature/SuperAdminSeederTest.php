<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_seeder_creates_default_super_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->where('email', 'admin@test.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_seeder_does_not_reset_existing_password(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->where('email', 'admin@test.com')->firstOrFail();
        $user->forceFill(['password' => Hash::make('changed-secret')])->save();

        $this->seed(SuperAdminSeeder::class);

        $this->assertTrue(Hash::check('changed-secret', $user->fresh()->password));
        $this->assertTrue($user->fresh()->hasRole('super-admin'));
    }

    public function test_production_skips_without_env_credentials(): void
    {
        $this->app['env'] = 'production';

        (new RolesAndPermissionsSeeder)->run();
        (new SuperAdminSeeder)->run();

        $this->assertSame(0, User::query()->count());
    }
}
