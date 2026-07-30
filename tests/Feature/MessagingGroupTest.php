<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MessagingGroupTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $memberA;

    private User $memberB;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findOrCreate('agent_courrier', 'web');

        $this->owner = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'name' => 'Owner Chat',
        ]);
        $this->owner->assignRole($role);

        $this->memberA = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'name' => 'Member A',
        ]);
        $this->memberA->assignRole($role);

        $this->memberB = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'name' => 'Member B',
        ]);
        $this->memberB->assignRole($role);
    }

    public function test_user_can_create_group_with_members(): void
    {
        $response = $this->actingAs($this->owner)->post(route('messages.group.create'), [
            'title' => 'Équipe Accueil',
            'members' => [$this->memberA->id, $this->memberB->id],
        ]);

        $conversation = Conversation::query()->where('type', 'group')->where('title', 'Équipe Accueil')->first();
        $this->assertNotNull($conversation);

        $response->assertRedirect(route('messages.show', $conversation));

        $participantIds = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            collect([$this->owner->id, $this->memberA->id, $this->memberB->id])->sort()->values()->all(),
            $participantIds
        );
    }

    public function test_group_creation_requires_members(): void
    {
        $this->actingAs($this->owner)
            ->from(route('messages.index'))
            ->post(route('messages.group.create'), [
                'title' => 'Groupe vide',
                'members' => [],
            ])
            ->assertRedirect(route('messages.index'))
            ->assertSessionHasErrors('members');

        $this->assertSame(0, Conversation::query()->where('type', 'group')->count());
    }

    public function test_group_creation_rejects_self_only_selection(): void
    {
        $this->actingAs($this->owner)
            ->from(route('messages.index'))
            ->post(route('messages.group.create'), [
                'title' => 'Moi seul',
                'members' => [$this->owner->id],
            ])
            ->assertRedirect(route('messages.index'))
            ->assertSessionHasErrors('members.0');
    }

    public function test_group_member_can_see_all_participants_on_show_page(): void
    {
        $conversation = $this->createGroupConversation();

        $this->actingAs($this->memberA)
            ->get(route('messages.show', $conversation))
            ->assertOk()
            ->assertSee('Owner Chat', false)
            ->assertSee('Member A', false)
            ->assertSee('Member B', false)
            ->assertSee(__('chat.view_members'), false)
            ->assertSee(__('chat.role_owner'), false);
    }

    public function test_owner_can_add_and_remove_members(): void
    {
        $conversation = $this->createGroupConversation(withMemberB: false);
        $this->assertFalse(
            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $this->memberB->id)
                ->exists()
        );

        $this->actingAs($this->owner)
            ->post(route('messages.members.add', $conversation), [
                'members' => [$this->memberB->id],
            ])
            ->assertRedirect();

        $this->assertTrue(
            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $this->memberB->id)
                ->exists()
        );

        $this->actingAs($this->owner)
            ->delete(route('messages.members.remove', [$conversation, $this->memberB]))
            ->assertRedirect();

        $this->assertFalse(
            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $this->memberB->id)
                ->exists()
        );
    }

    public function test_member_can_leave_group(): void
    {
        $conversation = $this->createGroupConversation();

        $this->actingAs($this->memberA)
            ->post(route('messages.leave', $conversation))
            ->assertRedirect(route('messages.index'));

        $this->assertFalse(
            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $this->memberA->id)
                ->exists()
        );
    }

    private function createGroupConversation(bool $withMemberB = true): Conversation
    {
        $members = [$this->memberA->id];
        if ($withMemberB) {
            $members[] = $this->memberB->id;
        }

        $this->actingAs($this->owner)->post(route('messages.group.create'), [
            'title' => 'Équipe Accueil',
            'members' => $members,
        ]);

        return Conversation::query()
            ->where('type', 'group')
            ->where('title', 'Équipe Accueil')
            ->firstOrFail();
    }
}
