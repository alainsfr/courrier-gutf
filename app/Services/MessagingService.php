<?php

namespace App\Services;

use App\Events\UserChatUnread;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MessagingService
{
    /**
     * Conversations de l'utilisateur, avec dernier message + compteur non-lus.
     *
     * @return Collection<int, Conversation>
     */
    public function conversationsFor(User $user, ?string $search = null): Collection
    {
        $userId = (int) $user->id;

        $query = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->with([
                'participants.user:id,name,email,avatar_path',
                'messages' => fn ($q) => $q->latest('id')->limit(1),
            ])
            ->withCount([
                'messages as unread_count' => fn ($q) => $this->applyUnreadConstraint($q, $userId),
            ])
            ->latest('updated_at');

        if ($search !== null && trim($search) !== '') {
            $term = '%'.mb_strtolower(trim($search)).'%';
            $query->where(function (Builder $q) use ($term, $userId) {
                $q->whereRaw('LOWER(COALESCE(title, "")) LIKE ?', [$term])
                    ->orWhereHas('participants.user', function (Builder $uq) use ($term, $userId) {
                        $uq->where('users.id', '!=', $userId)
                            ->where(function (Builder $nameQ) use ($term) {
                                $nameQ->whereRaw('LOWER(users.name) LIKE ?', [$term])
                                    ->orWhereRaw('LOWER(users.email) LIKE ?', [$term]);
                            });
                    })
                    ->orWhereHas('messages', function (Builder $mq) use ($term) {
                        $mq->whereRaw('LOWER(body) LIKE ?', [$term]);
                    });
            });
        }

        return $query->get();
    }

    /**
     * Utilisateurs actifs (hors soi) pour démarrer une discussion / inviter.
     *
     * @return Collection<int, User>
     */
    public function activeColleagues(User $user): Collection
    {
        return User::query()
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->whereKeyNot($user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'avatar_path']);
    }

    public function titleFor(Conversation $conversation, int $viewerId): string
    {
        if ($conversation->type === 'group') {
            return $conversation->title ?: __('chat.group');
        }

        $partner = $conversation->participants
            ->first(fn ($p) => (int) $p->user_id !== $viewerId)
            ?->user;

        return $partner?->name ?: __('chat.direct');
    }

    public function unreadTotalFor(User $user): int
    {
        $userId = (int) $user->id;

        return (int) Message::query()
            ->whereHas('conversation.participants', fn ($q) => $q->where('user_id', $userId))
            ->where('sender_user_id', '!=', $userId)
            ->whereRaw(
                'messages.created_at > COALESCE(
                    (SELECT last_read_at FROM conversation_participants
                     WHERE conversation_participants.conversation_id = messages.conversation_id
                       AND conversation_participants.user_id = ?),
                    \'1970-01-01\'
                )',
                [$userId]
            )
            ->count();
    }

    /**
     * Notifie les autres participants du total non-lus (badge widget / nav).
     */
    public function notifyUnreadForConversation(Conversation $conversation, int $exceptUserId): void
    {
        $participantIds = $conversation->participants()
            ->where('user_id', '!=', $exceptUserId)
            ->pluck('user_id');

        foreach ($participantIds as $pid) {
            $user = User::query()->find($pid);
            if (! $user) {
                continue;
            }

            broadcast(new UserChatUnread((int) $user->id, $this->unreadTotalFor($user)))->toOthers();
        }
    }

    public function applyUnreadConstraint(Builder $q, int $userId): void
    {
        $q->where('sender_user_id', '!=', $userId)
            ->whereRaw(
                'messages.created_at > COALESCE(
                    (SELECT last_read_at FROM conversation_participants
                     WHERE conversation_participants.conversation_id = messages.conversation_id
                       AND conversation_participants.user_id = ?),
                    \'1970-01-01\'
                )',
                [$userId]
            );
    }

    /**
     * Garantit qu'une conversation directe existe entre deux utilisateurs.
     */
    public function findOrCreateDirect(int $me, int $otherId): Conversation
    {
        return DB::transaction(function () use ($me, $otherId) {
            $existing = Conversation::query()
                ->where('type', 'direct')
                ->whereHas('participants', fn ($q) => $q->where('user_id', $me))
                ->whereHas('participants', fn ($q) => $q->where('user_id', $otherId))
                ->first();

            if ($existing) {
                return $existing;
            }

            $conv = Conversation::create([
                'type' => 'direct',
                'title' => null,
                'created_by_user_id' => $me,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $me,
                'role' => 'owner',
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $otherId,
                'role' => 'member',
            ]);

            return $conv;
        });
    }
}
