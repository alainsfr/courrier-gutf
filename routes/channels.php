<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ConversationParticipant;

Broadcast::channel('conversations.{conversationId}', function ($user, $conversationId) {
    return ConversationParticipant::where('conversation_id', (int) $conversationId)
        ->where('user_id', $user->id)
        ->exists();
});
