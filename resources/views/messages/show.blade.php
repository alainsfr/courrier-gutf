@php
    $chatPartner = null;
    if ($conversation->type === 'direct') {
        $chatPartner = $conversation->participants
            ->first(fn ($p) => (int) $p->user_id !== (int) auth()->id())
            ?->user;
    }
    $chatTitle = ($conversation->type === 'direct' && $chatPartner)
        ? $chatPartner->name
        : ($conversation->title ?? 'Groupe');
    $chatInitial = strtoupper(substr($chatTitle, 0, 1));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="mb-0 text-lg font-bold text-gray-900">{{ __('nav.messages') }}</h1>
            <p class="mb-0 text-sm text-gray-500">{{ $chatTitle }}</p>
        </div>
    </x-slot>

    <div class="flex h-[calc(100vh-8rem)] flex-col gap-4 lg:flex-row lg:gap-6">

        {{-- Liste (masquée sur mobile pendant le chat) --}}
        <div class="hidden min-h-0 flex-col lg:flex lg:w-1/3">
            @include('messages.partials.conversation-list')
        </div>

        {{-- Zone chat --}}
        <div class="flex min-h-0 flex-1 flex-col lg:w-2/3">
            <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                {{-- En-tête conversation --}}
                <div class="flex shrink-0 items-center gap-3 border-b border-gray-200 px-4 py-3.5">
                    <a href="{{ route('messages.index') }}"
                       class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 no-underline hover:bg-gray-50 lg:hidden"
                       aria-label="Retour">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </a>
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#17384b] text-sm font-bold text-white">
                        {{ $chatInitial }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-bold text-gray-900">{{ $chatTitle }}</div>
                        <div class="truncate text-xs text-gray-500">
                            @if($conversation->type === 'direct' && $chatPartner)
                                {{ $chatPartner->email }}
                            @else
                                {{ $conversation->participants->count() }} participant(s)
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Messages --}}
                <div id="messagesBox" class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-gray-50/60 p-5">
                    @foreach($conversation->messages as $m)
                        @php $isMe = ((int) $m->sender_user_id === (int) auth()->id()); @endphp
                        <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[80%] sm:max-w-[70%]">
                                @unless($isMe)
                                    <div class="mb-1 px-1 text-[11px] font-semibold text-gray-500">{{ $m->sender?->name }}</div>
                                @endunless
                                <div class="rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed shadow-sm
                                            {{ $isMe
                                                ? 'rounded-br-md bg-[#17384b] text-white'
                                                : 'rounded-bl-md border border-gray-200 bg-white text-gray-800' }}">
                                    <div class="whitespace-pre-line">{{ $m->body }}</div>
                                </div>
                                <div class="mt-1 px-1 text-[10px] text-gray-400 {{ $isMe ? 'text-right' : 'text-left' }}">
                                    {{ $m->created_at->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Saisie --}}
                @can('chat.send')
                    <div class="shrink-0 border-t border-gray-200 bg-white p-3 sm:p-4">
                        <form method="POST" action="{{ route('messages.send', $conversation) }}" id="sendForm" class="flex items-center gap-2">
                            @csrf
                            <input class="min-w-0 flex-1 rounded-full border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 transition focus:border-[#17384b] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#17384b]/20"
                                   name="body"
                                   id="msgInput"
                                   placeholder="{{ __('chat.write_placeholder') }}"
                                   required
                                   maxlength="2000"
                                   autocomplete="off">
                            <button type="submit"
                                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#17384b] text-white shadow-sm transition hover:bg-[#122c3c] focus:outline-none focus:ring-2 focus:ring-[#17384b]/40 focus:ring-offset-2"
                                    aria-label="{{ __('chat.send') }}"
                                    title="{{ __('chat.send') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                    <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const convId = {{ (int) $conversation->id }};
            const box = document.getElementById('messagesBox');
            const userId = {{ (int) auth()->id() }};
            let lastMessageCount = {{ $conversation->messages->count() }};

            if (box) box.scrollTop = box.scrollHeight;

            function bubbleHtml(body, isMe, senderName, createdAt) {
                const time = createdAt
                    ? new Date(createdAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
                    : '';
                const name = (!isMe && senderName)
                    ? `<div class="mb-1 px-1 text-[11px] font-semibold text-gray-500">${senderName}</div>`
                    : '';
                const bubble = isMe
                    ? 'rounded-2xl rounded-br-md bg-[#17384b] text-white'
                    : 'rounded-2xl rounded-bl-md border border-gray-200 bg-white text-gray-800';
                return `
                    <div class="flex ${isMe ? 'justify-end' : 'justify-start'}">
                        <div class="max-w-[80%] sm:max-w-[70%]">
                            ${name}
                            <div class="px-3.5 py-2.5 text-sm leading-relaxed shadow-sm ${bubble}">
                                <div class="whitespace-pre-line">${body}</div>
                            </div>
                            <div class="mt-1 px-1 text-[10px] text-gray-400 ${isMe ? 'text-right' : 'text-left'}">${time}</div>
                        </div>
                    </div>`;
            }

            function renderMessagesInFullPage(messages) {
                box.innerHTML = messages.map(m => {
                    const isMe = (m.sender_user_id === userId || m.sender?.id === userId);
                    return bubbleHtml(m.body, isMe, m.sender?.name, m.created_at);
                }).join('');
                box.scrollTop = box.scrollHeight;
            }

            if (window.Echo) {
                window.Echo.private(`conversations.${convId}`)
                    .listen('.message.sent', (e) => {
                        const isMe = (e.sender.id === userId);
                        const wrap = document.createElement('div');
                        wrap.innerHTML = bubbleHtml(e.body, isMe, e.sender.name, e.created_at);
                        box.appendChild(wrap.firstElementChild);
                        box.scrollTop = box.scrollHeight;
                        lastMessageCount++;
                    });
            }

            setInterval(async () => {
                if (!window.Echo || window.Echo.connector?.pusher?.connection?.state !== 'connected') {
                    try {
                        const res = await fetch(`/chat/mini/${convId}/messages`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const messages = await res.json();
                        if (messages.length > lastMessageCount) {
                            lastMessageCount = messages.length;
                            renderMessagesInFullPage(messages);
                        }
                    } catch (e) {}
                }
            }, 3500);
        });
    </script>
</x-app-layout>
