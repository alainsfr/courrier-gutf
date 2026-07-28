<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessagingController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->with([
                'participants.user:id,name,email',
                'messages' => fn ($q) => $q->latest()->limit(1),
            ])
            ->latest('updated_at')
            ->get();

        $users = User::orderBy('name')->get(['id','name','email']);

        return view('messages.index', compact('conversations', 'users'));
    }

    public function show(Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $conversation->load([
            'participants.user:id,name,email',
            'messages' => fn ($q) => $q->with('sender:id,name,email')->latest()->limit(200),
        ]);
        // Remettre chronologique pour l'UI
        $conversation->setRelation(
            'messages',
            $conversation->messages->sortBy('id')->values()
        );

        // mark read
        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);

        return view('messages.show', compact('conversation'));
    }

    public function startDirect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required','integer','exists:users,id', Rule::notIn([Auth::id()])],
        ]);

        $otherId = (int) $validated['user_id'];
        $me = Auth::id();

        $conversation = DB::transaction(function () use ($me, $otherId) {
            // find existing direct conversation with exactly 2 members
            $existing = Conversation::query()
                ->where('type', 'direct')
                ->whereHas('participants', fn ($q) => $q->where('user_id', $me))
                ->whereHas('participants', fn ($q) => $q->where('user_id', $otherId))
                ->first();

            if ($existing) return $existing;

            $conv = Conversation::create([
                'type' => 'direct',
                'title' => null,
                'created_by_user_id' => $me,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $me,
                'role' => 'owner',
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $otherId,
                'role' => 'member',
            ]);

            return $conv;
        });

        return redirect()->route('messages.show', $conversation);
    }

    public function createGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required','string','max:150'],
            'members' => ['required','array','min:1'],
            'members.*' => ['integer','exists:users,id'],
        ]);

        $me = Auth::id();

        $conv = DB::transaction(function () use ($validated, $me) {
            $conv = Conversation::create([
                'type' => 'group',
                'title' => $validated['title'],
                'created_by_user_id' => $me,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $me,
                'role' => 'owner',
            ]);

            foreach (array_unique($validated['members']) as $uid) {
                if ((int)$uid === (int)$me) continue;
                ConversationParticipant::create([
                    'conversation_id' => $conv->id,
                    'user_id' => (int)$uid,
                    'role' => 'member',
                ]);
            }

            return $conv;
        });

        return redirect()->route('messages.show', $conv);
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('send', $conversation);

        $validated = $request->validate([
            'body' => ['required','string','max:2000'],
        ]);

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        // broadcast
        broadcast(new MessageSent($msg))->toOthers();

        return back();
    }
}
