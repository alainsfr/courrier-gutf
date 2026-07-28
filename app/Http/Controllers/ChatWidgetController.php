<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatWidgetController extends Controller
{
    /**
     * Récupère la liste simplifiée des conversations récentes pour le widget
     */
    public function mini(Request $request)
    {
        $userId = Auth::id();

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->with([
                'messages' => fn ($q) => $q->latest()->limit(1),
                'participants.user:id,name',
            ])
            ->withCount([
                // Un seul sous-select par conversation (évite le N+1 sur messages()->count())
                'messages as unread_count' => function ($q) use ($userId) {
                    $q->whereRaw(
                        'messages.created_at > COALESCE(
                            (SELECT last_read_at FROM conversation_participants
                             WHERE conversation_participants.conversation_id = messages.conversation_id
                               AND conversation_participants.user_id = ?),
                            \'1970-01-01\'
                        )',
                        [$userId]
                    );
                },
            ])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        $items = [];
        $unreadTotal = 0;

        foreach ($conversations as $c) {
            $unread = (int) $c->unread_count;
            $unreadTotal += $unread;

            $title = __('chat.group');
            if ($c->type === 'group') {
                $title = $c->title ?: __('chat.group');
            } else {
                $partner = $c->participants->first(fn ($part) => $part->user_id !== $userId)?->user;
                $title = $partner ? $partner->name : __('chat.direct');
            }

            $lastMsg = $c->messages->first();
            $last = $lastMsg ? \Illuminate\Support\Str::limit($lastMsg->body, 70) : '—';

            $items[] = [
                'id' => $c->id,
                'title' => $title,
                'last' => $last,
                'unread' => $unread,
            ];
        }

        return response()->json([
            'unread_total' => $unreadTotal,
            'conversations' => $items,
        ]);
    }

    /**
     * API : Récupère les derniers messages d'une discussion pour le mini-chat
     */
    public function getMessages(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->latest()
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * API : Envoie un message rapide depuis le mini-chat
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $this->authorize('send', $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        broadcast(new \App\Events\MessageSent($msg))->toOthers();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'body' => $msg->body,
                'sender' => ['id' => Auth::id(), 'name' => Auth::user()->name],
                'created_at' => $msg->created_at->toISOString(),
            ],
        ]);
    }
}
