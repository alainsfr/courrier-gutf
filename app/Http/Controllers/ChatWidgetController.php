<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatWidgetController extends Controller
{
    public function __construct(private MessagingService $messaging)
    {
    }

    /**
     * Liste simplifiée des conversations récentes pour le widget
     */
    public function mini(Request $request)
    {
        $user = Auth::user();
        $userId = (int) $user->id;

        $conversations = $this->messaging->conversationsFor($user)->take(20);

        $items = [];
        $unreadTotal = 0;

        foreach ($conversations as $c) {
            $unread = (int) $c->unread_count;
            $unreadTotal += $unread;

            $title = $this->messaging->titleFor($c, $userId);
            $lastMsg = $c->messages->first();
            $last = $lastMsg ? Str::limit($lastMsg->body, 70) : '—';

            $items[] = [
                'id' => $c->id,
                'title' => $title,
                'last' => $last,
                'unread' => $unread,
                'updated_at' => optional($c->updated_at)?->toISOString(),
            ];
        }

        return response()->json([
            'unread_total' => $unreadTotal,
            'conversations' => $items,
        ]);
    }

    /**
     * API : derniers messages d'une discussion pour le mini-chat
     */
    public function getMessages(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * API : envoi rapide depuis le mini-chat
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
            'body' => trim($validated['body']),
        ]);

        $conversation->touch();

        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);

        broadcast(new MessageSent($msg))->toOthers();
        $this->messaging->notifyUnreadForConversation($conversation, (int) Auth::id());

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
