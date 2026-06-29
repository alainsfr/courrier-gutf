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
            ->whereHas('participants', fn($q) => $q->where('user_id', $userId))
            ->with([
                'messages' => fn($q) => $q->latest()->limit(1),
                'participants.user:id,name'
            ])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        $parts = ConversationParticipant::where('user_id', $userId)
            ->get()
            ->keyBy('conversation_id');

        $items = [];
        $unreadTotal = 0;

        foreach ($conversations as $c) {
            $p = $parts->get($c->id);
            $lastRead = $p?->last_read_at;

            $unread = $lastRead 
                ? $c->messages()->where('created_at', '>', $lastRead)->count()
                : $c->messages()->count();

            $unreadTotal += $unread;

            // Détection du titre bilingue ou du nom du correspondant
            $title = __('chat.group');
            if ($c->type === 'group') {
                $title = $c->title ?: __('chat.group');
            } else {
                $partner = $c->participants->first(fn($part) => $part->user_id !== $userId)?->user;
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
        // Sécurité : Vérifier que l'utilisateur est bien membre de la conversation
        abort_unless($conversation->participants()->where('user_id', Auth::id())->exists(), 403);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->latest()
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        // Marquer la conversation comme lue par la même occasion
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
        abort_unless($conversation->participants()->where('user_id', Auth::id())->exists(), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        // Diffusion temps réel via WebSockets
        broadcast(new \App\Events\MessageSent($msg))->toOthers();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'body' => $msg->body,
                'sender' => ['id' => Auth::id(), 'name' => Auth::user()->name],
                'created_at' => $msg->created_at->toISOString(),
            ]
        ]);
    }
}