@php
    $chatPartner = null;
    if ($conversation->type === 'direct') {
        $chatPartner = $conversation->participants
            ->first(fn ($p) => (int) $p->user_id !== (int) auth()->id())
            ?->user;
    }
    $chatTitle = $chatTitle ?? (($conversation->type === 'direct' && $chatPartner)
        ? $chatPartner->name
        : ($conversation->title ?? __('chat.group')));
    $chatInitial = strtoupper(mb_substr($chatTitle, 0, 1));
    $isGroup = $conversation->isGroup();
    $memberCount = $conversation->participants->count();
    $field = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 transition focus:border-[#17384b] focus:outline-none focus:ring-2 focus:ring-[#17384b]/20';
    $messageSearch = $messageSearch ?? '';
    $locale = app()->getLocale();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="mb-0 text-lg font-bold text-gray-900">{{ __('nav.messages') }}</h1>
            <p class="mb-0 text-sm text-gray-500">{{ $chatTitle }}</p>
        </div>
    </x-slot>

    <div class="flex h-[calc(100vh-8rem)] flex-col gap-4 lg:flex-row lg:gap-6">

        <div class="hidden min-h-0 flex-col lg:flex lg:w-1/3">
            @include('messages.partials.conversation-list')
        </div>

        <div class="flex min-h-0 flex-1 flex-col lg:w-2/3">
            <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="flex shrink-0 flex-col gap-2 border-b border-gray-200 px-4 py-3.5">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('messages.index') }}"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 no-underline hover:bg-gray-50 lg:hidden"
                           aria-label="{{ __('chat.back') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                            </svg>
                        </a>
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white {{ $isGroup ? 'bg-[#dc633a]' : 'bg-[#17384b]' }}">
                            {{ $chatInitial }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-gray-900">{{ $chatTitle }}</div>
                            <div class="truncate text-xs text-gray-500">
                                @if ($isGroup)
                                    {{ trans_choice('chat.members_count', $memberCount, ['count' => $memberCount]) }}
                                @elseif ($chatPartner)
                                    {{ $chatPartner->email }}
                                @endif
                            </div>
                        </div>

                        @if ($isGroup)
                            <div class="hidden items-center -space-x-2 sm:flex">
                                @foreach ($conversation->participants->take(4) as $p)
                                    <x-user-avatar :user="$p->user" size="sm" class="ring-2 ring-white" />
                                @endforeach
                                @if ($memberCount > 4)
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600 ring-2 ring-white">
                                        +{{ $memberCount - 4 }}
                                    </span>
                                @endif
                            </div>
                            <button type="button"
                                    data-members-open
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-[#17384b] hover:bg-slate-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                <span class="hidden sm:inline">{{ __('chat.view_members') }}</span>
                            </button>
                        @endif
                    </div>

                    <form method="GET" action="{{ route('messages.show', $conversation) }}" class="flex gap-2">
                        @if (($search ?? '') !== '')
                            <input type="hidden" name="q" value="{{ $search }}">
                        @endif
                        <input type="search"
                               name="mq"
                               value="{{ $messageSearch }}"
                               placeholder="{{ __('chat.search_in_conversation') }}"
                               class="min-w-0 flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-700 placeholder:text-gray-400 focus:border-[#17384b] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#17384b]/20"
                               autocomplete="off">
                        @if ($messageSearch !== '')
                            <a href="{{ route('messages.show', $conversation) }}"
                               class="inline-flex items-center rounded-lg border border-gray-200 px-2.5 text-xs font-semibold text-gray-600 no-underline hover:bg-gray-50">
                                {{ __('chat.clear_search') }}
                            </a>
                        @endif
                    </form>
                </div>

                @if (session('status'))
                    <div class="shrink-0 border-b border-emerald-100 bg-emerald-50 px-4 py-2 text-xs text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <div id="messagesBox" class="min-h-0 flex-1 space-y-1 overflow-y-auto bg-gray-50/60 p-4 sm:p-5" data-locale="{{ $locale }}">
                    @if ($conversation->messages->isEmpty())
                        <div id="emptyThread" class="flex h-full flex-col items-center justify-center py-16 text-center">
                            <div class="mb-3 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-[#17384b] shadow-sm ring-1 ring-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-7 w-7">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                </svg>
                            </div>
                            <p class="mb-1 text-sm font-semibold text-gray-800">
                                {{ $messageSearch !== '' ? __('chat.search_empty') : __('chat.no_messages') }}
                            </p>
                            <p class="mb-0 max-w-xs text-xs text-gray-500">{{ __('chat.start_conversation_hint') }}</p>
                        </div>
                    @else
                        @php $lastDateKey = null; @endphp
                        @foreach ($conversation->messages as $m)
                            @php
                                $isMe = ((int) $m->sender_user_id === (int) auth()->id());
                                $dateKey = $m->created_at->toDateString();
                                if ($m->created_at->isToday()) {
                                    $dateLabel = __('chat.today');
                                } elseif ($m->created_at->isYesterday()) {
                                    $dateLabel = __('chat.yesterday');
                                } else {
                                    $dateLabel = $m->created_at->translatedFormat('d M Y');
                                }
                            @endphp
                            @if ($lastDateKey !== $dateKey)
                                <div class="flex items-center gap-3 py-3" data-date-sep="{{ $dateKey }}">
                                    <div class="h-px flex-1 bg-gray-200"></div>
                                    <span class="shrink-0 rounded-full bg-white px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500 ring-1 ring-gray-200">{{ $dateLabel }}</span>
                                    <div class="h-px flex-1 bg-gray-200"></div>
                                </div>
                                @php $lastDateKey = $dateKey; @endphp
                            @endif
                            <div class="group flex {{ $isMe ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $m->id }}" data-date="{{ $dateKey }}">
                                <div class="max-w-[85%] sm:max-w-[70%]">
                                    @unless ($isMe)
                                        <div class="mb-1 px-1 text-[11px] font-semibold text-gray-500">{{ $m->sender?->name }}</div>
                                    @endunless
                                    <div class="relative rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed shadow-sm
                                                {{ $isMe
                                                    ? 'rounded-br-md bg-[#17384b] text-white'
                                                    : 'rounded-bl-md border border-gray-200 bg-white text-gray-800' }}">
                                        <div class="whitespace-pre-line break-words">{{ $m->body }}</div>
                                        @if ($isMe)
                                            <form method="POST"
                                                  action="{{ route('messages.destroy', [$conversation, $m]) }}"
                                                  class="absolute -left-8 top-1/2 hidden -translate-y-1/2 group-hover:block"
                                                  data-delete-message
                                                  onsubmit="return confirm(@json(__('chat.confirm_delete_message')));">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-200 bg-white text-rose-500 shadow-sm hover:bg-rose-50"
                                                        title="{{ __('chat.delete_message') }}"
                                                        aria-label="{{ __('chat.delete_message') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="mt-1 px-1 text-[10px] text-gray-400 {{ $isMe ? 'text-right' : 'text-left' }}">
                                        {{ $m->created_at->format('H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                @can('chat.send')
                    <div class="shrink-0 border-t border-gray-200 bg-white p-3 sm:p-4">
                        <form method="POST" action="{{ route('messages.send', $conversation) }}" id="sendForm" class="flex items-end gap-2">
                            @csrf
                            <textarea class="min-h-[44px] max-h-36 min-w-0 flex-1 resize-none rounded-2xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 transition focus:border-[#17384b] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#17384b]/20"
                                   name="body"
                                   id="msgInput"
                                   rows="1"
                                   placeholder="{{ __('chat.write_placeholder') }}"
                                   required
                                   maxlength="2000"
                                   autocomplete="off"></textarea>
                            <button type="submit"
                                    id="sendBtn"
                                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#17384b] text-white shadow-sm transition hover:bg-[#122c3c] focus:outline-none focus:ring-2 focus:ring-[#17384b]/40 focus:ring-offset-2 disabled:opacity-50"
                                    aria-label="{{ __('chat.send') }}"
                                    title="{{ __('chat.send') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                    <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                                </svg>
                            </button>
                        </form>
                        <p class="mb-0 mt-1.5 px-1 text-[10px] text-gray-400">{{ __('chat.send_hint') }}</p>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    @if ($isGroup)
        <div id="membersOverlay" class="fixed inset-0 z-[70] hidden bg-slate-900/40" data-members-close aria-hidden="true"></div>
        <aside id="membersPanel"
               class="fixed inset-y-0 right-0 z-[80] flex w-full max-w-md translate-x-full flex-col border-l border-gray-200 bg-white shadow-2xl transition-transform duration-200"
               aria-hidden="true">
            <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3.5">
                <div>
                    <h2 class="mb-0 text-sm font-bold text-gray-900">{{ __('chat.group_members') }}</h2>
                    <p class="mb-0 text-xs text-gray-500">{{ $chatTitle }} · {{ trans_choice('chat.members_count', $memberCount, ['count' => $memberCount]) }}</p>
                </div>
                <button type="button" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50" data-members-close>
                    {{ __('chat.close') }}
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                @if ($canManageGroup)
                    <form method="POST" action="{{ route('messages.rename', $conversation) }}" class="mb-5 rounded-xl border border-gray-200 p-3">
                        @csrf
                        @method('PATCH')
                        <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400">{{ __('chat.rename_group') }}</h3>
                        <input class="mb-2 {{ $field }}" name="title" value="{{ old('title', $conversation->title) }}" required maxlength="150">
                        <x-buttons.secondary type="submit" class="w-full">
                            {{ __('chat.save_name') }}
                        </x-buttons.secondary>
                    </form>
                @endif

                <div class="space-y-2">
                    @foreach ($conversation->participants as $p)
                        @php
                            $member = $p->user;
                            $isMe = $member && (int) $member->id === (int) auth()->id();
                            $isOwnerRole = $p->role === 'owner';
                        @endphp
                        <div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-slate-50/80 px-3 py-2.5">
                            <x-user-avatar :user="$member" size="sm" />
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-gray-900">
                                    {{ $member?->name ?? '—' }}
                                    @if ($isMe)
                                        <span class="text-xs font-medium text-gray-400">({{ __('chat.you') }})</span>
                                    @endif
                                </div>
                                <div class="truncate text-xs text-gray-500">{{ $member?->email }}</div>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide
                                         {{ $isOwnerRole ? 'bg-[#17384b]/10 text-[#17384b]' : 'bg-slate-200 text-slate-600' }}">
                                {{ $isOwnerRole ? __('chat.role_owner') : __('chat.role_member') }}
                            </span>
                            @if ($canManageGroup && ! $isMe && ! $isOwnerRole)
                                <form method="POST" action="{{ route('messages.members.remove', [$conversation, $member]) }}"
                                      onsubmit="return confirm(@json(__('chat.confirm_remove_member', ['name' => $member?->name])));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                        {{ __('chat.remove') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($canManageGroup && $eligibleMembers->isNotEmpty())
                    <form method="POST" action="{{ route('messages.members.add', $conversation) }}" class="mt-5 rounded-xl border border-gray-200 p-3">
                        @csrf
                        <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400">{{ __('chat.add_members') }}</h3>
                        <div class="mb-3 max-h-40 space-y-1 overflow-y-auto">
                            @foreach ($eligibleMembers as $u)
                                <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-50">
                                    <input type="checkbox" name="members[]" value="{{ $u->id }}"
                                           class="h-4 w-4 rounded border-gray-300 text-[#17384b] focus:ring-[#17384b]/30">
                                    <span class="min-w-0 flex-1 truncate">
                                        <span class="font-medium text-gray-800">{{ $u->name }}</span>
                                        <span class="block truncate text-[11px] text-gray-400">{{ $u->email }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <x-buttons.primary type="submit" class="w-full">
                            {{ __('chat.add_selected') }}
                        </x-buttons.primary>
                    </form>
                @endif
            </div>

            <div class="border-t border-gray-200 p-4">
                <form method="POST" action="{{ route('messages.leave', $conversation) }}"
                      onsubmit="return confirm(@json(__('chat.confirm_leave')));">
                    @csrf
                    <button type="submit" class="w-full rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                        {{ __('chat.leave_group') }}
                    </button>
                </form>
            </div>
        </aside>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const convId = {{ (int) $conversation->id }};
            const box = document.getElementById('messagesBox');
            const form = document.getElementById('sendForm');
            const input = document.getElementById('msgInput');
            const sendBtn = document.getElementById('sendBtn');
            const userId = {{ (int) auth()->id() }};
            const locale = box?.dataset.locale || 'fr';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                || form?.querySelector('input[name="_token"]')?.value;
            const labels = {
                today: @json(__('chat.today')),
                yesterday: @json(__('chat.yesterday')),
                deleteConfirm: @json(__('chat.confirm_delete_message')),
                deleteTitle: @json(__('chat.delete_message')),
                sendError: @json(__('chat.send_error')),
            };
            let knownIds = new Set(@json($conversation->messages->pluck('id')->values()));

            const escapeHtml = (s) => {
                const d = document.createElement('div');
                d.textContent = s ?? '';
                return d.innerHTML;
            };

            const dateKeyOf = (iso) => {
                const d = new Date(iso);
                return d.toISOString().slice(0, 10);
            };

            const dateLabelOf = (iso) => {
                const d = new Date(iso);
                const today = new Date();
                const y = new Date();
                y.setDate(today.getDate() - 1);
                const key = dateKeyOf(iso);
                if (key === today.toISOString().slice(0, 10)) return labels.today;
                if (key === y.toISOString().slice(0, 10)) return labels.yesterday;
                return d.toLocaleDateString(locale === 'en' ? 'en-GB' : 'fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
            };

            const ensureDateSep = (iso) => {
                const key = dateKeyOf(iso);
                if (box.querySelector(`[data-date-sep="${key}"]`)) return;
                const sep = document.createElement('div');
                sep.className = 'flex items-center gap-3 py-3';
                sep.setAttribute('data-date-sep', key);
                sep.innerHTML = `
                    <div class="h-px flex-1 bg-gray-200"></div>
                    <span class="shrink-0 rounded-full bg-white px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500 ring-1 ring-gray-200">${escapeHtml(dateLabelOf(iso))}</span>
                    <div class="h-px flex-1 bg-gray-200"></div>`;
                box.appendChild(sep);
            };

            const removeEmpty = () => document.getElementById('emptyThread')?.remove();

            const deleteUrl = (messageId) => @json(url('/messages/'.$conversation->id.'/messages')).replace(/\/?$/, '/') + messageId;

            function bubbleEl(payload) {
                const isMe = payload.isMe;
                const time = payload.createdAt
                    ? new Date(payload.createdAt).toLocaleTimeString(locale === 'en' ? 'en-GB' : 'fr-FR', { hour: '2-digit', minute: '2-digit' })
                    : '';
                const wrap = document.createElement('div');
                wrap.className = `group flex ${isMe ? 'justify-end' : 'justify-start'}`;
                wrap.dataset.messageId = payload.id || '';
                wrap.dataset.date = payload.createdAt ? dateKeyOf(payload.createdAt) : '';
                const name = (!isMe && payload.senderName)
                    ? `<div class="mb-1 px-1 text-[11px] font-semibold text-gray-500">${escapeHtml(payload.senderName)}</div>`
                    : '';
                const bubble = isMe
                    ? 'rounded-2xl rounded-br-md bg-[#17384b] text-white'
                    : 'rounded-2xl rounded-bl-md border border-gray-200 bg-white text-gray-800';
                const del = isMe && payload.id ? `
                    <form method="POST" action="${deleteUrl(payload.id)}" class="absolute -left-8 top-1/2 hidden -translate-y-1/2 group-hover:block" data-delete-message>
                        <input type="hidden" name="_token" value="${csrf}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-200 bg-white text-rose-500 shadow-sm hover:bg-rose-50" title="${escapeHtml(labels.deleteTitle)}" aria-label="${escapeHtml(labels.deleteTitle)}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </form>` : '';
                wrap.innerHTML = `
                    <div class="max-w-[85%] sm:max-w-[70%]">
                        ${name}
                        <div class="relative px-3.5 py-2.5 text-sm leading-relaxed shadow-sm ${bubble}">
                            <div class="whitespace-pre-line break-words">${escapeHtml(payload.body)}</div>
                            ${del}
                        </div>
                        <div class="mt-1 px-1 text-[10px] text-gray-400 ${isMe ? 'text-right' : 'text-left'}">${time}</div>
                    </div>`;
                return wrap;
            }

            function appendMessage(payload) {
                if (payload.id && knownIds.has(payload.id)) return;
                if (payload.id) knownIds.add(payload.id);
                removeEmpty();
                if (payload.createdAt) ensureDateSep(payload.createdAt);
                box.appendChild(bubbleEl(payload));
                box.scrollTop = box.scrollHeight;
            }

            if (box) box.scrollTop = box.scrollHeight;

            // Auto-resize textarea
            const autoGrow = () => {
                if (!input) return;
                input.style.height = 'auto';
                input.style.height = Math.min(input.scrollHeight, 144) + 'px';
            };
            input?.addEventListener('input', autoGrow);

            input?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form?.requestSubmit();
                }
            });

            // AJAX send
            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const body = (input?.value || '').trim();
                if (!body) return;

                sendBtn && (sendBtn.disabled = true);
                const optimisticId = 'tmp-' + Date.now();
                appendMessage({
                    id: optimisticId,
                    body,
                    isMe: true,
                    senderName: null,
                    createdAt: new Date().toISOString(),
                });
                input.value = '';
                autoGrow();

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ body }),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error('send failed');

                    const tmp = box.querySelector(`[data-message-id="${optimisticId}"]`);
                    knownIds.delete(optimisticId);
                    if (tmp) tmp.remove();
                    appendMessage({
                        id: data.message.id,
                        body: data.message.body,
                        isMe: true,
                        senderName: null,
                        createdAt: data.message.created_at,
                    });
                } catch (err) {
                    box.querySelector(`[data-message-id="${optimisticId}"]`)?.remove();
                    knownIds.delete(optimisticId);
                    alert(labels.sendError);
                    input.value = body;
                    autoGrow();
                } finally {
                    sendBtn && (sendBtn.disabled = false);
                    input?.focus();
                }
            });

            // AJAX delete
            box?.addEventListener('submit', async (e) => {
                const delForm = e.target.closest('[data-delete-message]');
                if (!delForm) return;
                e.preventDefault();
                if (!confirm(labels.deleteConfirm)) return;
                try {
                    const res = await fetch(delForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: new URLSearchParams({ _method: 'DELETE', _token: csrf }),
                    });
                    if (!res.ok) throw new Error('delete failed');
                    const row = delForm.closest('[data-message-id]');
                    const id = Number(row?.dataset.messageId);
                    if (id) knownIds.delete(id);
                    row?.remove();
                } catch (err) {
                    alert(labels.sendError);
                }
            });

            const panel = document.getElementById('membersPanel');
            const overlay = document.getElementById('membersOverlay');
            const openMembers = () => {
                panel?.classList.remove('translate-x-full');
                overlay?.classList.remove('hidden');
                panel?.setAttribute('aria-hidden', 'false');
                overlay?.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
            };
            const closeMembers = () => {
                panel?.classList.add('translate-x-full');
                overlay?.classList.add('hidden');
                panel?.setAttribute('aria-hidden', 'true');
                overlay?.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            };
            document.querySelectorAll('[data-members-open]').forEach((btn) => btn.addEventListener('click', openMembers));
            document.querySelectorAll('[data-members-close]').forEach((btn) => btn.addEventListener('click', closeMembers));
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMembers(); });

            @if ($errors->has('members') || $errors->has('members.0') || $errors->has('title'))
                openMembers();
            @endif

            if (window.Echo) {
                window.Echo.private(`conversations.${convId}`)
                    .listen('.message.sent', (e) => {
                        appendMessage({
                            id: e.id,
                            body: e.body,
                            isMe: e.sender.id === userId,
                            senderName: e.sender.name,
                            createdAt: e.created_at,
                        });
                    });
            }

            setInterval(async () => {
                if (window.Echo && window.Echo.connector?.pusher?.connection?.state === 'connected') return;
                try {
                    const res = await fetch(`/chat/mini/${convId}/messages`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const messages = await res.json();
                    (messages || []).forEach((m) => {
                        appendMessage({
                            id: m.id,
                            body: m.body,
                            isMe: (m.sender_user_id === userId || m.sender?.id === userId),
                            senderName: m.sender?.name,
                            createdAt: m.created_at,
                        });
                    });
                } catch (e) {}
            }, 4000);
        });
    </script>
</x-app-layout>
