<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminBackupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        Role::findOrCreate('super-admin', 'web');

        Storage::fake('backups');

        $this->admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->admin->assignRole('super-admin');

        $this->agent = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->agent->assignRole('agent_courrier');
    }

    public function test_super_admin_can_view_backups_page(): void
    {
        $backupName = (string) config('backup.backup.name');
        Storage::disk('backups')->put($backupName.'/demo-backup.zip', 'zip-content');

        $this->actingAs($this->admin)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee(__('backups.pra_title'), false)
            ->assertSee('demo-backup.zip', false);
    }

    public function test_non_admin_cannot_access_backups(): void
    {
        $this->actingAs($this->agent)
            ->get(route('admin.backups.index'))
            ->assertForbidden();
    }
}
