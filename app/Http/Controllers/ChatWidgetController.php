<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatWidgetController extends Controller
{
    public function mini(Request $request)
    {
        $userId = Auth::id();

        $conversations = Conversation::query()
            ->whereHas('participants', fn($q) => $q->where('user_id', $userId))
            ->with([
                'messages' => fn($q) => $q->latest()->limit(1),
            ])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        // Unread per conversation (simple V1: last_read_at)
        $parts = ConversationParticipant::where('user_id', $userId)
            ->get()
            ->keyBy('conversation_id');

        $items = [];
        $unreadTotal = 0;

        foreach ($conversations as $c) {
            $p = $parts->get($c->id);
            $lastRead = $p?->last_read_at;

            $unread = 0;
            if ($lastRead) {
                $unread = $c->messages()->where('created_at', '>', $lastRead)->count();
            } else {
                $unread = $c->messages()->count();
            }

            $unreadTotal += $unread;

            $title = $c->type === 'group' ? ($c->title ?: __('chat.group')) : __('chat.direct');

            $lastMsg = $c->messages->first();
            $last = $lastMsg ? \Illuminate\Support\Str::limit($lastMsg->body, 70) : '—';

            $items[] = [
                'id' => $c->id,
                'title' => $title,
                'last' => $last,
                'unread' => $unread,
                'url' => route('messages.show', $c),
            ];
        }

        return response()->json([
            'unread_total' => $unreadTotal,
            'conversations' => $items,
        ]);
    }
}
