<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdminAccessService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LastSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_last_active_super_admin_cannot_demote_themselves(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');
        $inactive = User::factory()->create(['is_active' => false]);
        $inactive->assignRole('super-admin');
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'locale' => 'fr', 'role' => 'consultation', 'is_active' => '1',
        ])->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->hasRole('super-admin'));
        $this->get(route('admin.users.create'))->assertOk()->assertSee('Agent de traitement')->assertSee('Comprendre les rôles');
    }

    public function test_demotion_is_possible_when_another_active_super_admin_remains(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');
        User::factory()->create(['is_active' => true])->assignRole('super-admin');
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'locale' => 'fr', 'role' => 'consultation', 'is_active' => '1',
        ])->assertRedirect();
        $this->assertFalse($admin->fresh()->hasRole('super-admin'));
        $this->assertSame(1, User::role('super-admin')->where('is_active', true)->count());
    }

    public function test_the_shared_guard_blocks_deleting_or_disabling_the_last_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');
        foreach (['delete', 'toggle'] as $action) {
            try {
                app(AdminAccessService::class)->change($admin, fn (User $user) => $user->delete(), delete: $action === 'delete', toggle: $action === 'toggle');
                $this->fail('The last administrator must remain active.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('role', $e->errors());
            }
        }
        $this->assertTrue($admin->fresh()->is_active);
    }
}
