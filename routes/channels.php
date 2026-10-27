<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ConversationParticipant;
use App\Models\Courrier;
use App\Models\Bureau;

Broadcast::channel('conversations.{conversationId}', function ($user, $conversationId) {
    return $user->is_active && $user->can('chat.view') && ConversationParticipant::where('conversation_id', (int) $conversationId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('courriers.{courrierId}', function ($user, $courrierId) {
    if (! $user->is_active || ! $user->can('courrier.view')) {
        return false;
    }

    if ($user->hasRole(['admin', 'super-admin'])) {
        return true;
    }

    $courrier = Courrier::query()->find((int) $courrierId);

    if (! $courrier) {
        return false;
    }

    return $user->can('view', $courrier);
});

Broadcast::channel('bureaux.{bureauId}', function ($user, $bureauId) {
    return $user->is_active && $user->can('courrier.view')
        && ($user->hasAnyRole(['admin', 'super-admin']) || (int) $user->bureau_id === (int) $bureauId)
        && Bureau::query()->whereKey((int) $bureauId)->exists();
});

Broadcast::channel('users.{userId}', fn ($user, $userId) => $user->is_active && (int) $user->id === (int) $userId);
