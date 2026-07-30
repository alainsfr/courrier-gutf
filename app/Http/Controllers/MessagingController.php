<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessagingController extends Controller
{
    public function __construct(private MessagingService $messaging)
    {
    }

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $search = $request->string('q')->trim()->toString() ?: null;

        $conversations = $this->messaging->conversationsFor($user, $search);
        $users = $this->messaging->activeColleagues($user);

        return view('messages.index', [
            'conversations' => $conversations,
            'users' => $users,
            'search' => $search ?? '',
            'unreadTotal' => (int) $conversations->sum('unread_count'),
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        /** @var User $user */
        $user = Auth::user();
        $userId = (int) $user->id;
        $search = $request->string('q')->trim()->toString() ?: null;
        $messageSearch = $request->string('mq')->trim()->toString() ?: null;

        $messagesQuery = $conversation->messages()
            ->with('sender:id,name,email,avatar_path')
            ->latest('id')
            ->limit(200);

        if ($messageSearch) {
            $term = '%'.mb_strtolower($messageSearch).'%';
            $messagesQuery->whereRaw('LOWER(body) LIKE ?', [$term]);
        }

        $messages = $messagesQuery->get()->sortBy('id')->values();

        $conversation->load([
            'participants' => fn ($q) => $q->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")->orderBy('id'),
            'participants.user:id,name,email,avatar_path',
        ]);
        $conversation->setRelation('messages', $messages);

        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);

        $conversations = $this->messaging->conversationsFor($user, $search);
        // Active conversation should show 0 unread in the sidebar
        $conversations->each(function (Conversation $c) use ($conversation) {
            if ((int) $c->id === (int) $conversation->id) {
                $c->unread_count = 0;
            }
        });

        $memberIds = $conversation->participants->pluck('user_id')->all();
        $users = $this->messaging->activeColleagues($user);

        $eligibleMembers = $conversation->isGroup()
            ? $users->reject(fn (User $u) => in_array($u->id, $memberIds, true))->values()
            : collect();

        $canManageGroup = $conversation->isGroup()
            && $user->can('manage', $conversation);

        return view('messages.show', [
            'conversation' => $conversation,
            'conversations' => $conversations,
            'users' => $users,
            'eligibleMembers' => $eligibleMembers,
            'canManageGroup' => $canManageGroup,
            'search' => $search ?? '',
            'messageSearch' => $messageSearch ?? '',
            'unreadTotal' => (int) $conversations->sum('unread_count'),
            'chatTitle' => $this->messaging->titleFor($conversation, $userId),
        ]);
    }

    public function startDirect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', Rule::notIn([Auth::id()])],
        ]);

        $conversation = $this->messaging->findOrCreateDirect((int) Auth::id(), (int) $validated['user_id']);

        return redirect()->route('messages.show', $conversation);
    }

    public function createGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'members' => ['required', 'array', 'min:1'],
            'members.*' => [
                'integer',
                'distinct',
                'exists:users,id',
                Rule::notIn([Auth::id()]),
            ],
        ], [
            'title.required' => __('chat.group_title_required'),
            'members.required' => __('chat.group_members_required'),
            'members.min' => __('chat.group_members_required'),
        ]);

        $me = (int) Auth::id();
        $memberIds = collect($validated['members'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== $me)
            ->unique()
            ->values();

        if ($memberIds->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['members' => __('chat.group_members_required')]);
        }

        $activeMemberIds = User::query()
            ->whereIn('id', $memberIds)
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->pluck('id');

        if ($activeMemberIds->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['members' => __('chat.group_members_required')]);
        }

        $conv = DB::transaction(function () use ($validated, $me, $activeMemberIds) {
            $conv = Conversation::create([
                'type' => 'group',
                'title' => trim($validated['title']),
                'created_by_user_id' => $me,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $me,
                'role' => 'owner',
            ]);

            foreach ($activeMemberIds as $uid) {
                ConversationParticipant::create([
                    'conversation_id' => $conv->id,
                    'user_id' => (int) $uid,
                    'role' => 'member',
                ]);
            }

            return $conv;
        });

        return redirect()
            ->route('messages.show', $conv)
            ->with('status', __('chat.group_created'));
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
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

        $msg->load('sender:id,name,email,avatar_path');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $msg->id,
                    'body' => $msg->body,
                    'conversation_id' => $msg->conversation_id,
                    'sender_user_id' => $msg->sender_user_id,
                    'created_at' => $msg->created_at->toISOString(),
                    'sender' => [
                        'id' => $msg->sender->id,
                        'name' => $msg->sender->name,
                        'email' => $msg->sender->email,
                    ],
                ],
            ]);
        }

        return back();
    }

    public function destroyMessage(Request $request, Conversation $conversation, Message $message): RedirectResponse|JsonResponse
    {
        abort_unless((int) $message->conversation_id === (int) $conversation->id, 404);
        $this->authorize('delete', $message);

        $message->delete();
        $conversation->touch();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'id' => $message->id]);
        }

        return back()->with('status', __('chat.message_deleted'));
    }

    public function rename(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('manage', $conversation);
        abort_unless($conversation->isGroup(), 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
        ], [
            'title.required' => __('chat.group_title_required'),
        ]);

        $conversation->update(['title' => trim($validated['title'])]);

        return back()->with('status', __('chat.group_renamed'));
    }

    public function addMembers(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('manage', $conversation);
        abort_unless($conversation->isGroup(), 404);

        $validated = $request->validate([
            'members' => ['required', 'array', 'min:1'],
            'members.*' => [
                'integer',
                'distinct',
                'exists:users,id',
                Rule::notIn([Auth::id()]),
            ],
        ], [
            'members.required' => __('chat.group_members_required'),
            'members.min' => __('chat.group_members_required'),
        ]);

        $existing = $conversation->participants()->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        $toAdd = User::query()
            ->whereIn('id', $validated['members'])
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => in_array($id, $existing, true))
            ->unique()
            ->values();

        if ($toAdd->isEmpty()) {
            return back()->withErrors(['members' => __('chat.group_members_none_new')]);
        }

        foreach ($toAdd as $uid) {
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $uid,
                'role' => 'member',
            ]);
        }

        $conversation->touch();

        return back()->with('status', trans_choice('chat.group_members_added', $toAdd->count(), ['count' => $toAdd->count()]));
    }

    public function removeMember(Conversation $conversation, User $user): RedirectResponse
    {
        $this->authorize('manage', $conversation);
        abort_unless($conversation->isGroup(), 404);

        if ((int) $user->id === (int) Auth::id()) {
            return back()->withErrors(['members' => __('chat.group_cannot_remove_self')]);
        }

        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return back()->withErrors(['members' => __('chat.group_member_missing')]);
        }

        if ($participant->role === 'owner') {
            return back()->withErrors(['members' => __('chat.group_cannot_remove_owner')]);
        }

        $participant->delete();
        $conversation->touch();

        return back()->with('status', __('chat.group_member_removed', ['name' => $user->name]));
    }

    public function leave(Conversation $conversation): RedirectResponse
    {
        $this->authorize('leave', $conversation);
        abort_unless($conversation->isGroup(), 404);

        $me = (int) Auth::id();

        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $me)
            ->firstOrFail();

        $remaining = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $me)
            ->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        if ($participant->role === 'owner' && $remaining->isNotEmpty()) {
            $successor = $remaining->first();
            $successor->forceFill(['role' => 'owner'])->save();
        }

        $participant->delete();

        if ($remaining->isEmpty()) {
            $conversation->delete();

            return redirect()
                ->route('messages.index')
                ->with('status', __('chat.group_left_and_deleted'));
        }

        $conversation->touch();

        return redirect()
            ->route('messages.index')
            ->with('status', __('chat.group_left'));
    }
}
