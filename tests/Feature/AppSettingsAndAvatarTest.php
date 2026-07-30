<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AppSettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AppSettingsAndAvatarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findOrCreate('super-admin', 'web');
        $role->syncPermissions(
            \Spatie\Permission\Models\Permission::query()->where('guard_name', 'web')->get()
        );

        $this->admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'locale' => 'fr',
        ]);
        $this->admin->assignRole($role);

        Storage::fake('public');
    }

    public function test_admin_can_update_branding_settings(): void
    {
        $logo = UploadedFile::fake()->image('logo.png', 120, 120);

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'app_name' => 'Courrier Test',
                'app_tagline' => 'Tagline test',
                'country' => 'Cameroun',
                'motto' => 'Paix',
                'ministry' => 'MINDCAF',
                'structure' => 'GUTF',
                'support_email' => 'support@example.com',
                'primary_color' => '#112233',
                'accent_color' => '#aabbcc',
                'logo' => $logo,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $settings = app(AppSettingsService::class);
        $settings->forgetCache();

        $this->assertSame('Courrier Test', $settings->get('app_name'));
        $this->assertSame('#112233', $settings->get('primary_color'));
        $this->assertNotNull($settings->get('logo_path'));
        Storage::disk('public')->assertExists($settings->get('logo_path'));
    }

    public function test_admin_can_create_user_with_avatar(): void
    {
        $avatar = UploadedFile::fake()->image('photo.jpg', 200, 200);
        $agentRole = Role::findOrCreate('consultation', 'web');

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Agent Photo',
                'email' => 'agent.photo@example.com',
                'locale' => 'fr',
                'role' => $agentRole->name,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
                'avatar' => $avatar,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'agent.photo@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
        $this->assertNotNull($user->avatarUrl());
    }

    public function test_settings_page_is_reachable(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee(__('settings.title'), false);
    }
}
