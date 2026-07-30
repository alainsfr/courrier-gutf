<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CourrierStatusNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findOrCreate('consultation', 'web');
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'locale' => 'fr',
        ]);
        $this->user->assignRole($role);
    }

    public function test_notifications_index_and_unread_count(): void
    {
        $this->user->notify(new CourrierStatusNotification(
            'Titre test',
            'Message test',
            url('/dashboard'),
            ['type' => 'assigned']
        ));

        $this->actingAs($this->user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Titre test');

        $this->actingAs($this->user)
            ->getJson(route('notifications.unread_count'))
            ->assertOk()
            ->assertJson(['count' => 1]);
    }

    public function test_open_marks_notification_as_read_and_redirects(): void
    {
        $this->user->notify(new CourrierStatusNotification(
            'À ouvrir',
            'Aller au dashboard',
            url('/dashboard'),
            ['type' => 'info']
        ));

        $notification = $this->user->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        $this->actingAs($this->user)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(url('/dashboard'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read(): void
    {
        $this->user->notify(new CourrierStatusNotification('A', 'a', url('/dashboard')));
        $this->user->notify(new CourrierStatusNotification('B', 'b', url('/dashboard')));

        $this->assertSame(2, $this->user->unreadNotifications()->count());

        $this->actingAs($this->user)
            ->patch(route('notifications.read_all'))
            ->assertRedirect();

        $this->assertSame(0, $this->user->fresh()->unreadNotifications()->count());
    }

    public function test_cannot_open_another_users_notification(): void
    {
        $other = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $other->notify(new CourrierStatusNotification('Privé', 'secret', url('/dashboard')));
        $notification = $other->notifications()->first();

        $this->actingAs($this->user)
            ->get(route('notifications.open', $notification->id))
            ->assertNotFound();
    }

    public function test_rejects_external_open_url(): void
    {
        Notification::send($this->user, new CourrierStatusNotification(
            'Phish',
            'bad link',
            'https://evil.example/steal',
            ['type' => 'info']
        ));

        $notification = $this->user->unreadNotifications()->first();

        $this->actingAs($this->user)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
