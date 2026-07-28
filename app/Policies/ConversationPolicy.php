<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $user->can('chat.view')
            && $conversation->participants()->where('user_id', $user->id)->exists();
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $user->can('chat.send')
            && $conversation->participants()->where('user_id', $user->id)->exists();
    }
}
