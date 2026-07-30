<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function delete(User $user, Message $message): bool
    {
        if (! $user->can('chat.send')) {
            return false;
        }

        if ((int) $message->sender_user_id !== (int) $user->id) {
            return false;
        }

        return $message->conversation
            ->participants()
            ->where('user_id', $user->id)
            ->exists();
    }
}
