@props([
    'initialUnread' => 0,
])

@unless(request()->routeIs('messages.*'))
<div class="chat-fab no-print">
    <button type="button" class="chat-fab-btn" id="chatFabBtn" aria-label="{{ __('chat.title') }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c0-4.556 4.253-8.25 9.75-8.25s9.75 3.694 9.75 8.25-4.253 8.25-9.75 8.25a10.9 10.9 0 0 1-3.84-.69L4.5 20.25l.86-2.72A7.8 7.8 0 0 1 2.25 12.75Z" />
        </svg>
        <span class="chat-badge" id="chatUnreadBadge" style="{{ $initialUnread > 0 ? '' : 'display:none;' }}">
            {{ $initialUnread }}
        </span>
    </button>
</div>

<div class="chat-panel no-print" id="chatPanel" aria-hidden="true">
    {{-- Liste --}}
    <div id="widgetListView" class="flex h-full flex-col">
        <div class="chat-panel-header">
            <div>
                <div class="chat-panel-title">{{ __('chat.title') }}</div>
                <div class="text-[11px] font-medium text-slate-400">{{ __('nav.messages') }}</div>
            </div>
            <button type="button" class="chat-icon-btn" id="chatCloseBtn" aria-label="{{ __('chat.close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="chat-panel-body flex-1 overflow-y-auto" id="chatListContainer">
            <div class="px-3 py-6 text-center text-xs text-slate-400">{{ __('chat.loading') }}</div>
        </div>

        <div class="chat-panel-footer">
            <a class="chat-full-link" href="{{ route('messages.index') }}">
                {{ __('chat.open_full') }}
            </a>
        </div>
    </div>

    {{-- Conversation --}}
    <div id="widgetChatView" class="hidden h-full flex-col">
        <div class="chat-panel-header">
            <div class="flex min-w-0 items-center gap-2">
                <button type="button" class="chat-icon-btn" id="widgetBackBtn" aria-label="{{ __('chat.back') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                </button>
                <div class="min-w-0">
                    <div class="truncate text-sm font-bold text-brand" id="widgetChatTitle">—</div>
                </div>
            </div>
            <button type="button" class="chat-icon-btn" id="widgetChatCloseBtn" aria-label="{{ __('chat.close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="chat-panel-body flex-1 overflow-y-auto bg-slate-50" id="widgetMessagesContainer"></div>

        <div class="chat-panel-footer">
            <form id="widgetSendForm" class="flex items-center gap-2">
                <input class="chat-input" id="widgetMsgInput" placeholder="{{ __('chat.write_placeholder') }}" required maxlength="1000" autocomplete="off">
                <button class="chat-send-btn" type="submit" aria-label="{{ __('chat.send') }}" title="{{ __('chat.send') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                        <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('chatFabBtn');
    const panel = document.getElementById('chatPanel');
    const closeBtn = document.getElementById('chatCloseBtn');
    const listContainer = document.getElementById('chatListContainer');
    const badge = document.getElementById('chatUnreadBadge');
    const listView = document.getElementById('widgetListView');
    const chatView = document.getElementById('widgetChatView');
    const backBtn = document.getElementById('widgetBackBtn');
    const chatCloseBtn = document.getElementById('widgetChatCloseBtn');
    const chatTitle = document.getElementById('widgetChatTitle');
    const messagesContainer = document.getElementById('widgetMessagesContainer');
    const sendForm = document.getElementById('widgetSendForm');
    const msgInput = document.getElementById('widgetMsgInput');
    const headerChatBadge = document.getElementById('headerChatBadge');

    if (!btn || !panel) return;

    let activeConversationId = null;
    let echoChannelInstance = null;
    let lastWidgetMessageCount = 0;
    const userId = {{ (int) auth()->id() }};

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setUnread(n) {
        if (badge) {
            if (n > 0) {
                badge.style.display = 'flex';
                badge.textContent = String(n);
            } else {
                badge.style.display = 'none';
            }
        }
        if (headerChatBadge) {
            if (n > 0) {
                headerChatBadge.style.display = 'inline-block';
                headerChatBadge.textContent = String(n);
            } else {
                headerChatBadge.style.display = 'none';
            }
        }
    }

    function toggle(open) {
        panel.classList.toggle('open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (!open) unsubscribeActiveChannel();
    }

    function unsubscribeActiveChannel() {
        if (window.Echo && echoChannelInstance && activeConversationId) {
            window.Echo.leave(`conversations.${activeConversationId}`);
            echoChannelInstance = null;
        }
    }

    function showList() {
        chatView.classList.add('hidden');
        chatView.classList.remove('flex');
        listView.classList.remove('hidden');
        listView.classList.add('flex');
    }

    function showChat() {
        listView.classList.add('hidden');
        listView.classList.remove('flex');
        chatView.classList.remove('hidden');
        chatView.classList.add('flex');
    }

    function renderConversationList(conversations) {
        if (!conversations || conversations.length === 0) {
            listContainer.innerHTML = `<div class="px-3 py-8 text-center text-xs text-slate-400">{{ __('chat.empty') }}</div>`;
            return;
        }

        listContainer.innerHTML = conversations.map(c => `
            <button type="button" class="chat-conv" data-id="${c.id}" data-title="${escapeHtml(c.title)}">
                <div class="flex items-center justify-between gap-2">
                    <div class="truncate text-sm font-semibold text-slate-800">${escapeHtml(c.title)}</div>
                    ${c.unread > 0 ? `<span class="chat-unread-pill">${c.unread}</span>` : ''}
                </div>
                <div class="mt-1 truncate text-xs text-slate-400">${escapeHtml(c.last || '—')}</div>
            </button>
        `).join('');

        listContainer.querySelectorAll('.chat-conv').forEach(el => {
            el.addEventListener('click', () => openConversation(el.dataset.id, el.dataset.title));
        });
    }

    async function loadMiniInbox() {
        listContainer.innerHTML = `<div class="px-3 py-6 text-center text-xs text-slate-400">{{ __('chat.loading') }}</div>`;
        try {
            const res = await fetch(`{{ route('chat.mini') }}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const data = await res.json();
            setUnread(data.unread_total || 0);
            renderConversationList(data.conversations || []);
        } catch (e) {
            listContainer.innerHTML = `<div class="px-3 py-6 text-center text-xs text-rose-500">{{ __('chat.load_error') }}</div>`;
        }
    }

    function bubbleHtml(body, isMe, senderName) {
        return `
            <div class="mb-2 flex ${isMe ? 'justify-end' : 'justify-start'}">
                <div class="max-w-[85%]">
                    ${!isMe && senderName ? `<div class="mb-0.5 px-1 text-[10px] font-semibold text-slate-400">${escapeHtml(senderName)}</div>` : ''}
                    <div class="rounded-2xl px-3 py-2 text-[13px] leading-relaxed shadow-sm ${isMe
                        ? 'rounded-br-md bg-brand text-white'
                        : 'rounded-bl-md border border-slate-200 bg-white text-slate-800'}">
                        <div style="white-space:pre-line">${escapeHtml(body)}</div>
                    </div>
                </div>
            </div>`;
    }

    function renderMessages(messages) {
        if (!messages.length) {
            messagesContainer.innerHTML = `<div class="px-3 py-8 text-center text-xs text-slate-400">{{ __('chat.no_messages') }}</div>`;
            return;
        }
        messagesContainer.innerHTML = messages.map(m => {
            const isMe = (m.sender_user_id === userId || m.sender?.id === userId);
            return bubbleHtml(m.body, isMe, m.sender?.name);
        }).join('');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function appendSingleMessage(e) {
        const isMe = (e.sender.id === userId);
        const wrap = document.createElement('div');
        wrap.innerHTML = bubbleHtml(e.body, isMe, e.sender.name);
        messagesContainer.appendChild(wrap.firstElementChild);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function subscribeToChannel(convId) {
        if (window.Echo) {
            echoChannelInstance = window.Echo.private(`conversations.${convId}`)
                .listen('.message.sent', (e) => {
                    if (parseInt(activeConversationId) === parseInt(e.conversation_id)) {
                        appendSingleMessage(e);
                        lastWidgetMessageCount++;
                    }
                });
        }
    }

    async function openConversation(convId, title) {
        unsubscribeActiveChannel();
        activeConversationId = convId;
        chatTitle.textContent = title;
        showChat();
        messagesContainer.innerHTML = `<div class="px-3 py-6 text-center text-xs text-slate-400">{{ __('chat.loading') }}</div>`;

        try {
            const res = await fetch(`/chat/mini/${convId}/messages`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const messages = await res.json();
            lastWidgetMessageCount = messages.length;
            renderMessages(messages);
            loadMiniInbox();
            subscribeToChannel(convId);
        } catch (e) {
            messagesContainer.innerHTML = `<div class="px-3 py-6 text-center text-xs text-rose-500">{{ __('chat.load_error') }}</div>`;
        }
    }

    sendForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = msgInput.value.trim();
        if (!text || !activeConversationId) return;
        msgInput.value = '';

        try {
            const res = await fetch(`/chat/mini/${activeConversationId}/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ body: text })
            });
            const data = await res.json();
            if (data.success) {
                appendSingleMessage(data.message);
                lastWidgetMessageCount++;
            }
        } catch (err) {
            alert(@json(__('chat.send_error')));
        }
    });

    backBtn?.addEventListener('click', () => {
        unsubscribeActiveChannel();
        activeConversationId = null;
        showList();
        loadMiniInbox();
    });

    btn.addEventListener('click', async () => {
        const isOpen = panel.classList.contains('open');
        toggle(!isOpen);
        if (!isOpen) {
            showList();
            await loadMiniInbox();
        }
    });

    closeBtn?.addEventListener('click', () => toggle(false));
    chatCloseBtn?.addEventListener('click', () => toggle(false));

    if (window.Echo && userId) {
        window.Echo.private(`users.${userId}`)
            .listen('.chat.unread', (e) => {
                if (typeof e.unread_total === 'number') setUnread(e.unread_total);
                if (panel.classList.contains('open') && !activeConversationId) loadMiniInbox();
            });
    }

    setInterval(async () => {
        const echoDown = !window.Echo || window.Echo.connector?.pusher?.connection?.state !== 'connected';
        if (activeConversationId && echoDown) {
            try {
                const res = await fetch(`/chat/mini/${activeConversationId}/messages`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                const messages = await res.json();
                if (messages.length > lastWidgetMessageCount) {
                    lastWidgetMessageCount = messages.length;
                    renderMessages(messages);
                }
            } catch (e) {}
        }
        if (echoDown) {
            try {
                const res = await fetch(`{{ route('chat.mini') }}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                const data = await res.json();
                setUnread(data.unread_total || 0);
                if (panel.classList.contains('open') && !activeConversationId) {
                    renderConversationList(data.conversations || []);
                }
            } catch (e) {}
        }
    }, 3500);
});
</script>
@endunless
