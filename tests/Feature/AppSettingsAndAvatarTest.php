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
        $favicon = UploadedFile::fake()->create('favicon.ico', 8, 'image/x-icon');

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
                'favicon' => $favicon,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $settings = app(AppSettingsService::class);
        $settings->forgetCache();

        $this->assertSame('Courrier Test', $settings->get('app_name'));
        $this->assertSame('#112233', $settings->get('primary_color'));
        $this->assertNotNull($settings->get('logo_path'));
        $this->assertNotNull($settings->get('favicon_path'));
        Storage::disk('public')->assertExists($settings->get('logo_path'));
        Storage::disk('public')->assertExists($settings->get('favicon_path'));
    }

    public function test_admin_can_upload_svg_logo(): void
    {
        $svg = UploadedFile::fake()->createWithContent(
            'brand.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect width="64" height="64" fill="#17384b"/></svg>'
        );

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'app_name' => 'Courrier SVG',
                'primary_color' => '#17384b',
                'accent_color' => '#dc633a',
                'logo' => $svg,
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionDoesntHaveErrors();

        $settings = app(AppSettingsService::class);
        $settings->forgetCache();
        $this->assertNotNull($settings->get('logo_path'));
        Storage::disk('public')->assertExists($settings->get('logo_path'));
    }

    public function test_settings_rejects_invalid_logo_with_french_message(): void
    {
        $this->admin->forceFill(['locale' => 'fr'])->save();
        app()->setLocale('fr');

        $bad = UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream');

        $this->actingAs($this->admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'app_name' => 'Courrier Test',
                'primary_color' => '#17384b',
                'accent_color' => '#dc633a',
                'logo' => $bad,
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('logo');

        $errors = session('errors')->get('logo');
        $this->assertNotEmpty($errors);
        $this->assertStringNotContainsString('validation.', $errors[0]);
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

    public function test_branding_urls_are_host_relative(): void
    {
        $logo = UploadedFile::fake()->image('logo.png', 120, 120);

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'app_name' => 'Courrier Relative',
                'primary_color' => '#17384b',
                'accent_color' => '#dc633a',
                'logo' => $logo,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $settings = app(AppSettingsService::class);
        $settings->forgetCache();

        $url = $settings->logoUrl();
        $this->assertNotNull($url);
        $this->assertStringStartsWith('/storage/', $url);
        $this->assertStringNotContainsString('http://localhost/', $url);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($url, false);
    }

    public function test_branding_applies_on_welcome_and_login(): void
    {
        $logo = UploadedFile::fake()->image('home-logo.png', 120, 120);

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'app_name' => 'Accueil Brandé',
                'app_tagline' => 'Tagline accueil',
                'primary_color' => '#112233',
                'accent_color' => '#ff6600',
                'logo' => $logo,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $settings = app(AppSettingsService::class);
        $settings->forgetCache();
        $url = $settings->logoUrl();
        $this->assertNotNull($url);

        $this->get('/')
            ->assertOk()
            ->assertSee('Guichet Unique du Foncier', false)
            ->assertSee('Accueil Brandé', false)
            ->assertSee($url, false)
            ->assertSee('#112233', false);

        auth()->logout();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Accueil Brandé', false)
            ->assertSee($url, false)
            ->assertSee('#112233', false);
    }

    public function test_settings_page_is_reachable(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee(__('settings.title'), false);
    }
}
