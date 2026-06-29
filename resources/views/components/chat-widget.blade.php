@props([
    'initialUnread' => 0,
])

<div class="chat-fab no-print">
    <button type="button" class="chat-fab-btn" id="chatFabBtn" aria-label="Chat">
        💬
        <span class="chat-badge" id="chatUnreadBadge" style="{{ $initialUnread > 0 ? '' : 'display:none;' }}">
            {{ $initialUnread }}
        </span>
    </button>
</div>

<div class="chat-panel no-print" id="chatPanel" aria-hidden="true" style="width: 360px; height: 500px;">
    
    {{-- VUE 1 : LISTE DES DISCUSSIONS --}}
    <div id="widgetListView" class="h-100 d-flex flex-column">
        <div class="chat-panel-header">
            <div class="chat-panel-title">💬 {{ __('chat.title') }}</div>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1" id="chatCloseBtn">✕</button>
        </div>

        <div class="chat-panel-body flex-grow-1 overflow-y-auto p-3" id="chatListContainer">
            <div class="text-muted small">{{ __('chat.loading') }}</div>
        </div>

        <div class="chat-panel-footer border-top p-3 bg-white">
            <a class="btn btn-gutf btn-sm w-100 rounded-3 py-2 fw-semibold" href="{{ route('messages.index') }}">
                {{ __('chat.open_full') }}
            </a>
        </div>
    </div>

    {{-- VUE 2 : SALON DE CHAT ACTIF --}}
    <div id="widgetChatView" class="h-100 flex-column" style="display: none !important;">
        <div class="chat-panel-header d-flex align-items-center justify-content-between p-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-light border-0 py-1" id="widgetBackBtn">←</button>
                <div class="fw-bold text-dark text-truncate small" id="widgetChatTitle" style="max-width: 180px;">—</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1" id="widgetChatCloseBtn">✕</button>
        </div>

        <div class="chat-panel-body flex-grow-1 overflow-y-auto p-3 bg-light" id="widgetMessagesContainer" style="height: calc(100% - 120px);">
            {{-- Les bulles de messages se chargeront ici --}}
        </div>

        <div class="chat-panel-footer border-top p-3 bg-white">
            <form id="widgetSendForm">
                <div class="input-group">
                    <input class="form-control border-slate-200 py-2 small" id="widgetMsgInput" placeholder="Écrire un message..." required maxlength="1000" autocomplete="off">
                    <button class="btn btn-gutf btn-sm px-3" type="submit">➔</button>
                </div>
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

    // Éléments de la vue Chat actif
    const listView = document.getElementById('widgetListView');
    const chatView = document.getElementById('widgetChatView');
    const backBtn = document.getElementById('widgetBackBtn');
    const chatCloseBtn = document.getElementById('widgetChatCloseBtn');
    const chatTitle = document.getElementById('widgetChatTitle');
    const messagesContainer = document.getElementById('widgetMessagesContainer');
    const sendForm = document.getElementById('widgetSendForm');
    const msgInput = document.getElementById('widgetMsgInput');

    let activeConversationId = null;
    let echoChannelInstance = null;
    let lastWidgetMessageCount = 0;

    function setUnread(n) {
        if (!badge) return;
        if (n > 0) {
            badge.style.display = 'flex';
            badge.textContent = String(n);
        } else {
            badge.style.display = 'none';
        }
    }

    function toggle(open) {
        if (open) {
            panel.classList.add('open');
            panel.setAttribute('aria-hidden', 'false');
        } else {
            panel.classList.remove('open');
            panel.setAttribute('aria-hidden', 'true');
            unsubscribeActiveChannel();
        }
    }

    function unsubscribeActiveChannel() {
        if (window.Echo && echoChannelInstance && activeConversationId) {
            window.Echo.leave(`conversations.${activeConversationId}`);
            echoChannelInstance = null;
        }
    }

    // Chargement de la liste des conversations
    async function loadMiniInbox() {
        listContainer.innerHTML = `<div class="text-muted small text-center mt-3">{{ __('chat.loading') }}</div>`;
        try {
            const res = await fetch(`{{ route('chat.mini') }}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const data = await res.json();
            setUnread(data.unread_total || 0);

            if (!data.conversations || data.conversations.length === 0) {
                listContainer.innerHTML = `<div class="text-muted small text-center mt-3">{{ __('chat.empty') }}</div>`;
                return;
            }

            listContainer.innerHTML = data.conversations.map(c => `
                <div class="chat-conv p-3 border rounded-3 mb-2 hover:bg-light" style="cursor:pointer;" data-id="${c.id}" data-title="${c.title}">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="fw-bold text-dark small text-truncate" style="max-width: 220px;">${c.title}</div>
                        ${c.unread > 0 ? `<span class="badge text-bg-danger rounded-pill">${c.unread}</span>` : ``}
                    </div>
                    <div class="chat-conv-last text-muted small text-truncate mt-1" style="font-size:0.8rem;">${c.last || '—'}</div>
                </div>
            `).join('');

            listContainer.querySelectorAll('.chat-conv').forEach(el => {
                el.addEventListener('click', () => {
                    const id = el.getAttribute('data-id');
                    const title = el.getAttribute('data-title');
                    openConversation(id, title);
                });
            });
        } catch(e) {
            listContainer.innerHTML = `<div class="text-danger small text-center">Erreur de chargement.</div>`;
        }
    }

    // Ouverture d'une conversation à l'intérieur du widget
    async function openConversation(convId, title) {
        unsubscribeActiveChannel();
        activeConversationId = convId;
        chatTitle.textContent = title;

        listView.style.setProperty('display', 'none', 'important');
        chatView.style.setProperty('display', 'flex', 'important');

        messagesContainer.innerHTML = `<div class="text-muted small text-center mt-3">Chargement des messages...</div>`;
        
        try {
            const res = await fetch(`/chat/mini/${convId}/messages`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const messages = await res.json();
            lastWidgetMessageCount = messages.length;
            renderMessages(messages);

            loadMiniInbox();
            subscribeToChannel(convId);

        } catch (e) {
            messagesContainer.innerHTML = `<div class="text-danger small text-center">Impossible de charger la conversation.</div>`;
        }
    }

    // Rendu des messages sous forme de bulles
    function renderMessages(messages) {
        const userId = {{ (int) auth()->id() }};
        if (messages.length === 0) {
            messagesContainer.innerHTML = `<div class="text-muted small text-center mt-3">Aucun message dans ce salon.</div>`;
            return;
        }

        messagesContainer.innerHTML = messages.map(m => {
            const isMe = (m.sender_user_id === userId || m.sender.id === userId);
            return `
                <div class="d-flex ${isMe ? 'justify-content-end' : 'justify-content-start'} mb-2">
                    <div style="max-width: 85%;">
                        ${!isMe ? `<div class="text-muted small fw-semibold" style="font-size:0.75rem;">${m.sender.name}</div>` : ''}
                        <div class="p-2 rounded-3 shadow-sm mt-1 ${isMe ? 'bg-primary text-white rounded-tr-none' : 'bg-white text-dark rounded-tl-none border'}" style="font-size:0.85rem; white-space:pre-line;">${m.body}</div>
                    </div>
                </div>
            `;
        }).join('');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // S'inscrire à l'écoute en temps réel pour le salon actif
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

    // Ajouter un message reçu en temps réel
    function appendSingleMessage(e) {
        const userId = {{ (int) auth()->id() }};
        const isMe = (e.sender.id === userId);
        const wrap = document.createElement('div');
        
        wrap.className = `d-flex ${isMe ? 'justify-content-end' : 'justify-content-start'} mb-2`;
        wrap.innerHTML = `
            <div style="max-width: 85%;">
                ${!isMe ? `<div class="text-muted small fw-semibold" style="font-size:0.75rem;">${e.sender.name}</div>` : ''}
                <div class="p-2 rounded-3 shadow-sm mt-1 ${isMe ? 'bg-primary text-white rounded-tr-none' : 'bg-white text-dark rounded-tl-none border'}" style="font-size:0.85rem; white-space:pre-line;">${e.body}</div>
            </div>
        `;
        messagesContainer.appendChild(wrap);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Soumission du formulaire d'envoi rapide
    sendForm.addEventListener('submit', async (e) => {
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
            alert('Erreur lors de l\'envoi du message.');
        }
    });

    // Retour à la liste des discussions
    backBtn.addEventListener('click', () => {
        unsubscribeActiveChannel();
        activeConversationId = null;
        chatView.style.setProperty('display', 'none', 'important');
        listView.style.setProperty('display', 'flex', 'important');
        loadMiniInbox();
    });

    // Ouverture/Fermeture globale du widget
    btn?.addEventListener('click', async () => {
        const isOpen = panel.classList.contains('open');
        toggle(!isOpen);
        if (!isOpen) {
            chatView.style.setProperty('display', 'none', 'important');
            listView.style.setProperty('display', 'flex', 'important');
            await loadMiniInbox();
        }
    });

    closeBtn?.addEventListener('click', () => toggle(false));
    chatCloseBtn?.addEventListener('click', () => toggle(false));

    // Écoute globale des notifications non-lues (WebSockets)
    const userId = {{ (int) auth()->id() }};
    if (window.Echo && userId) {
        window.Echo.private(`users.${userId}`)
            .listen('.chat.unread', (e) => {
                if (typeof e.unread_total === 'number') {
                    setUnread(e.unread_total);
                }
                if (panel.classList.contains('open') && !activeConversationId) {
                    loadMiniInbox();
                }
            });
    }

    // SYSTÈME DE SECOURS INSTANTANÉ (Long-polling à 3,5s pour l'espace actif du widget) [a]
    setInterval(async () => {
        if (activeConversationId && (!window.Echo || window.Echo.connector.pusher.connection.state !== 'connected')) {
            try {
                const res = await fetch(`/chat/mini/${activeConversationId}/messages`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                const messages = await res.json();
                if (messages.length > lastWidgetMessageCount) {
                    lastWidgetMessageCount = messages.length;
                    renderMessages(messages);
                }
            } catch (e) {}
        }
    }, 3500);
});
</script>