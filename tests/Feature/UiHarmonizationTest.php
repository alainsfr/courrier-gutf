<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UiHarmonizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findOrCreate('super-admin', 'web');
        $role->syncPermissions(
            \Spatie\Permission\Models\Permission::query()->where('guard_name', 'web')->get()
        );

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'locale' => 'fr',
        ]);
        $this->user->assignRole($role);
    }

    public function test_main_ui_pages_return_ok_without_exceptions(): void
    {
        $paths = [
            '/dashboard',
            '/courriers',
            '/search',
            '/statistics',
        ];

        foreach ($paths as $path) {
            $response = $this->actingAs($this->user)->get($path);

            $response->assertOk();
        }

        $this->actingAs($this->user)
            ->get('/notifications')
            ->assertOk();
    }
}
