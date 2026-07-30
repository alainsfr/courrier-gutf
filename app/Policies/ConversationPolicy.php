<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $user->can('chat.view')
            && $this->isParticipant($user, $conversation);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $user->can('chat.send')
            && $this->isParticipant($user, $conversation);
    }

    public function manage(User $user, Conversation $conversation): bool
    {
        if ($conversation->type !== 'group') {
            return false;
        }

        if (! $this->isParticipant($user, $conversation)) {
            return false;
        }

        if ($user->can('chat.group.manage')) {
            return true;
        }

        return $this->isOwner($user, $conversation);
    }

    public function leave(User $user, Conversation $conversation): bool
    {
        return $conversation->type === 'group'
            && $this->isParticipant($user, $conversation);
    }

    private function isParticipant(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    private function isOwner(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists();
    }
}
