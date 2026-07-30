<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Services\MessagingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MessagingCompleteTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findOrCreate('agent_courrier', 'web');

        $this->alice = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'name' => 'Alice Chat',
        ]);
        $this->alice->assignRole($role);

        $this->bob = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'name' => 'Bob Chat',
        ]);
        $this->bob->assignRole($role);
    }

    private function directConversation(): Conversation
    {
        return app(MessagingService::class)->findOrCreateDirect($this->alice->id, $this->bob->id);
    }

    public function test_unread_excludes_own_messages_and_counts_others(): void
    {
        $conv = $this->directConversation();

        Message::create([
            'conversation_id' => $conv->id,
            'sender_user_id' => $this->alice->id,
            'body' => 'Hello from Alice',
        ]);

        Message::create([
            'conversation_id' => $conv->id,
            'sender_user_id' => $this->bob->id,
            'body' => 'Hello from Bob',
        ]);

        $service = app(MessagingService::class);
        $forAlice = $service->conversationsFor($this->alice);
        $forBob = $service->conversationsFor($this->bob);

        $this->assertSame(1, (int) $forAlice->firstWhere('id', $conv->id)->unread_count);
        $this->assertSame(1, (int) $forBob->firstWhere('id', $conv->id)->unread_count);

        ConversationParticipant::where('conversation_id', $conv->id)
            ->where('user_id', $this->alice->id)
            ->update(['last_read_at' => now()]);

        $forAlice = $service->conversationsFor($this->alice);
        $this->assertSame(0, (int) $forAlice->firstWhere('id', $conv->id)->unread_count);
    }

    public function test_ajax_send_returns_json_and_touches_conversation(): void
    {
        $conv = $this->directConversation();
        $before = $conv->fresh()->updated_at;

        $this->travel(2)->seconds();

        $response = $this->actingAs($this->alice)
            ->postJson(route('messages.send', $conv), ['body' => 'Message AJAX']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.body', 'Message AJAX');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'sender_user_id' => $this->alice->id,
            'body' => 'Message AJAX',
        ]);

        $this->assertTrue($conv->fresh()->updated_at->gt($before));
    }

    public function test_sender_can_delete_own_message(): void
    {
        $conv = $this->directConversation();
        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_user_id' => $this->alice->id,
            'body' => 'To delete',
        ]);

        $this->actingAs($this->alice)
            ->deleteJson(route('messages.destroy', [$conv, $msg]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('messages', ['id' => $msg->id]);
    }

    public function test_user_cannot_delete_others_message(): void
    {
        $conv = $this->directConversation();
        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_user_id' => $this->alice->id,
            'body' => 'Protected',
        ]);

        $this->actingAs($this->bob)
            ->deleteJson(route('messages.destroy', [$conv, $msg]))
            ->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $msg->id]);
    }

    public function test_owner_can_rename_group(): void
    {
        $this->actingAs($this->alice)->post(route('messages.group.create'), [
            'title' => 'Ancien nom',
            'members' => [$this->bob->id],
        ])->assertRedirect();

        $conv = Conversation::query()->where('type', 'group')->where('title', 'Ancien nom')->firstOrFail();

        $this->actingAs($this->alice)
            ->patch(route('messages.rename', $conv), ['title' => 'Nouveau nom'])
            ->assertRedirect();

        $this->assertSame('Nouveau nom', $conv->fresh()->title);
    }

    public function test_conversation_search_filters_by_title_or_message(): void
    {
        $conv = $this->directConversation();
        Message::create([
            'conversation_id' => $conv->id,
            'sender_user_id' => $this->alice->id,
            'body' => 'Dossier urgent SLA',
        ]);

        $other = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'name' => 'Charlie',
        ]);
        $other->assignRole(Role::findOrCreate('agent_courrier', 'web'));
        app(MessagingService::class)->findOrCreateDirect($this->alice->id, $other->id);

        $found = app(MessagingService::class)->conversationsFor($this->alice, 'urgent SLA');
        $this->assertTrue($found->contains('id', $conv->id));

        $miss = app(MessagingService::class)->conversationsFor($this->alice, 'inexistant-xyz');
        $this->assertFalse($miss->contains('id', $conv->id));
    }

    public function test_show_marks_conversation_as_read(): void
    {
        $conv = $this->directConversation();
        Message::create([
            'conversation_id' => $conv->id,
            'sender_user_id' => $this->bob->id,
            'body' => 'Unread for Alice',
        ]);

        $this->actingAs($this->alice)
            ->get(route('messages.show', $conv))
            ->assertOk();

        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conv->id)
            ->where('user_id', $this->alice->id)
            ->first();

        $this->assertNotNull($participant->last_read_at);
        $this->assertSame(0, (int) app(MessagingService::class)
            ->conversationsFor($this->alice)
            ->firstWhere('id', $conv->id)
            ->unread_count);
    }
}
