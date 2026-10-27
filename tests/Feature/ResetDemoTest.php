<?php

namespace Tests\Feature;

use App\Models\Courrier;
use App\Models\User;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResetDemoTest extends TestCase
{
    private string $sandbox;
    private string $originalStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        $this->seed([BureauSeeder::class, WorkflowEtapesSeeder::class, RolesAndPermissionsSeeder::class]);
        $this->originalStorage = app()->storagePath();
        $this->sandbox = sys_get_temp_dir().DIRECTORY_SEPARATOR.'courrier-reset-test-'.bin2hex(random_bytes(8));
        app()->useStoragePath($this->sandbox);
        foreach (['app/private', 'app/public', 'app/backups/old', 'framework', 'logs'] as $folder) {
            (new Filesystem)->ensureDirectoryExists(storage_path($folder));
        }
    }

    protected function tearDown(): void
    {
        app()->useStoragePath($this->originalStorage);
        RefreshDatabaseState::$migrated = false;
        $resolved = realpath($this->sandbox);
        if ($resolved && str_starts_with($resolved, realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'courrier-reset-test-')) {
            (new Filesystem)->deleteDirectory($resolved);
        }
        parent::tearDown();
    }

    public function test_reset_requires_explicit_confirmation_and_the_exact_database(): void
    {
        $this->artisan('app:reset-demo')->assertFailed();
        $this->artisan('app:reset-demo', [
            '--execute' => 'DELETE_OLD_DEMO_DATA', '--expected-database' => 'some-other-database',
        ])->assertFailed();
        $this->assertDatabaseCount('roles', 8);
        $this->assertFalse(app()->isDownForMaintenance());
    }

    public function test_reset_refuses_to_leave_the_application_without_an_administrator(): void
    {
        $this->artisan('app:reset-demo', [
            '--execute' => 'DELETE_OLD_DEMO_DATA', '--expected-database' => DB::connection()->getDatabaseName(),
        ])->assertFailed();
        $this->assertDatabaseCount('roles', 8);
    }

    public function test_reset_deletes_old_data_and_files_without_an_archive_and_keeps_only_admin_access(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');
        $password = $admin->getAuthPassword();
        User::factory()->create(['is_active' => true])->assignRole('agent_courrier');
        Courrier::create([
            'type' => 'entrant', 'objet' => 'Ancien dossier', 'reference_interne' => 'OLD-001',
            'year' => 2026, 'seq' => 1, 'created_by_user_id' => $admin->id,
        ]);
        file_put_contents(storage_path('app/private/old.pdf'), 'old document');
        file_put_contents(storage_path('app/public/old-avatar.png'), 'old image');
        file_put_contents(storage_path('app/backups/old/old.zip'), 'old backup');
        file_put_contents(storage_path('logs/old.log'), 'old log');

        $this->artisan('app:reset-demo', [
            '--execute' => 'DELETE_OLD_DEMO_DATA', '--expected-database' => DB::connection()->getDatabaseName(),
        ])->assertSuccessful();

        $this->assertDatabaseCount('courriers', 0);
        $this->assertDatabaseCount('courrier_histories', 0);
        $this->assertDatabaseCount('conversations', 0);
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('bureaux', 5);
        $this->assertDatabaseCount('roles', 8);
        $remaining = User::sole();
        $this->assertSame($admin->email, $remaining->email);
        $this->assertSame($password, $remaining->getAuthPassword());
        $this->assertTrue($remaining->hasRole('super-admin'));
        $this->assertNull($remaining->avatar_path);
        $this->assertNull($remaining->signature_pin);
        foreach (['app/private/old.pdf', 'app/public/old-avatar.png', 'app/backups/old/old.zip', 'logs/old.log'] as $file) {
            $this->assertFileDoesNotExist(storage_path($file));
        }
        $this->assertFalse(app()->isDownForMaintenance());
    }

    public function test_reset_recognizes_the_legacy_administrator_role_without_changing_credentials(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $password = $admin->getAuthPassword();
        $this->artisan('app:reset-demo', [
            '--execute' => 'DELETE_OLD_DEMO_DATA', '--expected-database' => DB::connection()->getDatabaseName(),
        ])->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($admin->email, User::sole()->email);
        $this->assertSame($password, User::sole()->getAuthPassword());
        $this->assertTrue(User::sole()->hasRole('super-admin'));
    }
}
