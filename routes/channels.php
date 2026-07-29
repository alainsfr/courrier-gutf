<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ConversationParticipant;
use App\Models\Courrier;
use App\Models\Bureau;

Broadcast::channel('conversations.{conversationId}', function ($user, $conversationId) {
    return ConversationParticipant::where('conversation_id', (int) $conversationId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('courriers.{courrierId}', function ($user, $courrierId) {
    if (! $user->can('courrier.view')) {
        return false;
    }

    if ($user->hasRole('admin')) {
        return true;
    }

    $courrier = Courrier::query()->find((int) $courrierId);

    if (! $courrier) {
        return false;
    }

    return (int) $courrier->created_by_user_id === (int) $user->id
        || (int) $courrier->assigned_to_user_id === (int) $user->id
        || ($user->can('courrier.validate') && $courrier->isEnValidation());
});

Broadcast::channel('bureaux.{bureauId}', function ($user, $bureauId) {
    return $user->can('courrier.view') && Bureau::query()->whereKey((int) $bureauId)->exists();
});
