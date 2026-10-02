<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserActivationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);

        $super = Role::findOrCreate('super-admin', 'web');
        $super->syncPermissions(
            \Spatie\Permission\Models\Permission::query()->where('guard_name', 'web')->get()
        );

        $this->admin = User::factory()->create([
            'email' => 'admin.active@test.com',
            'email_verified_at' => now(),
            'is_active' => true,
            'locale' => 'fr',
        ]);
        $this->admin->assignRole($super);

        $this->agent = User::factory()->create([
            'email' => 'agent.active@test.com',
            'email_verified_at' => now(),
            'is_active' => true,
            'locale' => 'fr',
            'password' => 'password',
        ]);
        $this->agent->assignRole('agent_courrier');
    }

    public function test_admin_can_deactivate_user_via_update(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->agent), [
                'name' => $this->agent->name,
                'email' => $this->agent->email,
                'locale' => 'fr',
                'role' => 'agent_courrier',
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($this->agent->fresh()->is_active);
    }

    public function test_admin_can_reactivate_user_via_toggle(): void
    {
        $this->agent->forceFill(['is_active' => false])->save();

        $this->actingAs($this->admin)
            ->patch(route('admin.users.toggle-active', $this->agent))
            ->assertRedirect();

        $this->assertTrue($this->agent->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->admin), [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'locale' => 'fr',
                'role' => 'super-admin',
                'is_active' => '0',
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->agent->forceFill(['is_active' => false])->save();

        $this->post(route('login'), [
            'email' => 'agent.active@test.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_is_logged_out_by_middleware(): void
    {
        $this->actingAs($this->agent);

        $this->agent->forceFill(['is_active' => false])->save();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
