@props([
    'initialUnread' => 0,
])

<div class="chat-fab">
    <button type="button" class="chat-fab-btn" id="chatFabBtn" aria-label="Chat">
        💬
        <span class="chat-badge" id="chatUnreadBadge" style="{{ $initialUnread > 0 ? '' : 'display:none;' }}">
            {{ $initialUnread }}
        </span>
    </button>
</div>

<div class="chat-panel" id="chatPanel" aria-hidden="true">
    <div class="chat-panel-header">
        <div class="chat-panel-title">{{ __('chat.title') }}</div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="chatCloseBtn">✕</button>
    </div>

    <div class="chat-panel-body" id="chatPanelBody">
        <div class="text-muted small">{{ __('chat.loading') }}</div>
    </div>

    <div class="chat-panel-footer d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm w-100" href="{{ route('messages.index') }}">
            {{ __('chat.open_full') }}
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const btn = document.getElementById('chatFabBtn');
    const panel = document.getElementById('chatPanel');
    const closeBtn = document.getElementById('chatCloseBtn');
    const body = document.getElementById('chatPanelBody');
    const badge = document.getElementById('chatUnreadBadge');

    let unreadCount = parseInt(badge?.textContent || '0', 10);

    function setUnread(n){
        unreadCount = n;
        if (!badge) return;
        if (n > 0){
            badge.style.display = 'flex';
            badge.textContent = String(n);
        } else {
            badge.style.display = 'none';
            badge.textContent = '0';
        }
    }

    function toggle(open){
        if (open){
            panel.classList.add('open');
            panel.setAttribute('aria-hidden', 'false');
        } else {
            panel.classList.remove('open');
            panel.setAttribute('aria-hidden', 'true');
        }
    }

    async function loadMiniInbox(){
        body.innerHTML = `<div class="text-muted small">{{ __('chat.loading') }}</div>`;
        try{
            const res = await fetch(`{{ route('chat.mini') }}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const data = await res.json();

            setUnread(data.unread_total || 0);

            if (!data.conversations || data.conversations.length === 0){
                body.innerHTML = `<div class="text-muted">{{ __('chat.empty') }}</div>`;
                return;
            }

            body.innerHTML = data.conversations.map(c => `
                <div class="chat-conv" data-url="${c.url}">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="chat-conv-title">${c.title}</div>
                        ${c.unread > 0 ? `<span class="badge text-bg-danger">${c.unread}</span>` : ``}
                    </div>
                    <div class="chat-conv-last">${c.last || '—'}</div>
                </div>
            `).join('');

            body.querySelectorAll('.chat-conv').forEach(el => {
                el.addEventListener('click', () => {
                    window.location.href = el.getAttribute('data-url');
                });
            });
        } catch(e){
            body.innerHTML = `<div class="text-danger small">Erreur de chargement.</div>`;
        }
    }

    btn?.addEventListener('click', async () => {
        const isOpen = panel.classList.contains('open');
        toggle(!isOpen);
        if (!isOpen){
            await loadMiniInbox();
        }
    });

    closeBtn?.addEventListener('click', () => toggle(false));

    // --- Realtime (WebSockets) ---
    // On écoute un channel global "users.{id}" pour incrémenter le badge,
    // ET on rafraîchit la mini-inbox si le panneau est ouvert.
    const userId = {{ (int) auth()->id() }};
    if (window.Echo && userId){
        window.Echo.private(`users.${userId}`)
            .listen('.chat.unread', (e) => {
                // e.unread_total attendu
                if (typeof e.unread_total === 'number'){
                    setUnread(e.unread_total);
                } else {
                    setUnread(unreadCount + 1);
                }

                if (panel.classList.contains('open')){
                    loadMiniInbox();
                }
            });
    }
});
</script>
