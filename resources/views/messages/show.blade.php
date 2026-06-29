<x-app-layout>
    @php
        // Détection dynamique du collaborateur pour les discussions directes
        $chatPartner = null;
        if ($conversation->type === 'direct') {
            $chatPartner = $conversation->participants
                ->first(fn($p) => (int) $p->user_id !== (int) auth()->id())
                ?->user;
        }
    @endphp

    <div class="row g-4" style="height: calc(100vh - 180px); min-height: 500px;">
        
        {{-- COLONNE GAUCHE : NAVIGATION (1/3) --}}
        <div class="col-lg-4 d-none d-lg-flex flex-column h-100">
            <div class="card border-0 shadow-sm rounded-4 flex-grow-1 overflow-hidden d-flex flex-column">
                <div class="card-header bg-white p-4 border-bottom">
                    <h5 class="fw-bold text-dark mb-0">💬 Discussions</h5>
                </div>
                
                <div class="card-body p-4 overflow-y-auto" style="flex: 1;">
                    <div class="list-group list-group-flush">
                        {{-- Bouton de retour rapide vers l'accueil --}}
                        <a class="btn btn-outline-secondary w-100 py-2.5 rounded-3 fw-semibold mb-3 small" href="{{ route('messages.index') }}">
                            ← Liste de mes discussions
                        </a>

                        <hr class="my-3">

                        <div class="fw-bold text-muted small mb-2 text-uppercase" style="font-size: 0.75rem;">Membres du salon</div>
                        <div class="list-group list-group-flush border rounded-3 p-2 bg-light">
                            @foreach($conversation->participants as $p)
                                <div class="p-2 border-bottom last-border-0 d-flex align-items-center gap-2">
                                    <span class="small">👤</span>
                                    <div style="min-width: 0;">
                                        <div class="fw-semibold text-dark small text-truncate">{{ $p->user->name }}</div>
                                        <div class="text-muted small text-truncate" style="font-size: 0.75rem;">{{ $p->user->email }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- COLONNE DROITE : SALON DE DISCUSSION ACTIF (2/3) --}}
        <div class="col-lg-8 d-flex flex-column h-100">
            <div class="card border-0 shadow-sm rounded-4 flex-grow-1 overflow-hidden d-flex flex-column bg-white h-100">
                
                {{-- En-tête de la discussion --}}
                <div class="card-header bg-white p-4 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            @if($conversation->type === 'direct' && $chatPartner)
                                👤 {{ $chatPartner->name }}
                            @else
                                👥 {{ $conversation->title ?? 'Groupe' }}
                            @endif
                        </h5>
                        <p class="text-muted small mb-0">
                            @if($conversation->type === 'direct' && $chatPartner)
                                Discussion directe avec {{ $chatPartner->name }}
                            @else
                                {{ $conversation->participants->count() }} collaborateur(s) dans ce salon
                            @endif
                        </p>
                    </div>
                    <a class="btn btn-outline-secondary btn-sm rounded-3 d-lg-none" href="{{ route('messages.index') }}">← Retour</a>
                </div>

                {{-- Flux de messages (Bulles) --}}
                <div class="card-body p-4 overflow-y-auto bg-light" id="messagesBox" style="flex: 1;">
                    @foreach($conversation->messages as $m)
                        @php $isMe = ($m->sender_user_id === auth()->id()); @endphp
                        
                        <div class="d-flex {{ $isMe ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                            <div style="max-width: 75%;">
                                @if(!$isMe)
                                    <div class="text-muted small fw-semibold mb-1 ms-1">{{ $m->sender?->name }}</div>
                                @endif
                                
                                <div class="p-3 rounded-4 shadow-sm {{ $isMe ? 'bg-primary text-white rounded-tr-none' : 'bg-white text-dark rounded-tl-none border' }}">
                                    <div style="white-space: pre-line;" class="small">{{ $m->body }}</div>
                                </div>
                                
                                <div class="text-muted small mt-1 {{ $isMe ? 'text-end me-1' : 'text-start ms-1' }}" style="font-size: 0.75rem;">
                                    {{ $m->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Formulaire de saisie du message --}}
                @can('chat.send')
                    <div class="card-footer bg-white p-4 border-top">
                        <form method="POST" action="{{ route('messages.send', $conversation) }}" id="sendForm">
                            @csrf
                            <div class="input-group">
                                <input class="form-control border-slate-200 py-2.5 rounded-start-3" 
                                       name="body" 
                                       id="msgInput" 
                                       placeholder="Saisissez un message de collaboration..." 
                                       required 
                                       maxlength="2000"
                                       autocomplete="off">
                                <button class="btn btn-gutf px-4 rounded-end-3" type="submit">Envoyer ➔</button>
                            </div>
                        </form>
                    </div>
                @endcan

            </div>
        </div>

    </div>

    {{-- Script d'écoute du temps réel via WebSockets --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const convId = {{ (int) $conversation->id }};
            const box = document.getElementById('messagesBox');
            const input = document.getElementById('msgInput');
            let lastMessageCount = {{ $conversation->messages->count() }};

            if (box) {
                box.scrollTop = box.scrollHeight;
            }

            // Rendu des messages (utilisé pour le rafraîchissement d'arrière-plan)
            function renderMessagesInFullPage(messages) {
                const userId = {{ (int) auth()->id() }};
                box.innerHTML = messages.map(m => {
                    const isMe = (m.sender_user_id === userId || m.sender.id === userId);
                    return `
                        <div class="d-flex ${isMe ? 'justify-content-end' : 'justify-content-start'} mb-3">
                            <div style="max-width: 75%;">
                                ${!isMe ? `<div class="text-muted small fw-semibold mb-1 ms-1">${m.sender.name}</div>` : ''}
                                <div class="p-3 rounded-4 shadow-sm ${isMe ? 'bg-primary text-white rounded-tr-none' : 'bg-white text-dark rounded-tl-none border'}">
                                    <div style="white-space: pre-line;" class="small">${m.body}</div>
                                </div>
                                <div class="text-muted small mt-1 ${isMe ? 'text-end me-1' : 'text-start ms-1'}" style="font-size: 0.75rem;">
                                    ${new Date(m.created_at).toLocaleString('fr-FR', {day: 'numeric', month: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'})}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                box.scrollTop = box.scrollHeight;
            }

            // Écoute temps réel via WebSockets (Laravel Reverb)
            if (window.Echo) {
                window.Echo.private(`conversations.${convId}`)
                    .listen('.message.sent', (e) => {
                        const wrap = document.createElement('div');
                        const isMe = (e.sender.id === {{ (int) auth()->id() }});
                        
                        wrap.className = `d-flex ${isMe ? 'justify-content-end' : 'justify-content-start'} mb-3`;
                        wrap.innerHTML = `
                            <div style="max-width: 75%;">
                                ${!isMe ? `<div class="text-muted small fw-semibold mb-1 ms-1">${e.sender.name}</div>` : ''}
                                <div class="p-3 rounded-4 shadow-sm ${isMe ? 'bg-primary text-white rounded-tr-none' : 'bg-white text-dark rounded-tl-none border'}">
                                    <div style="white-space: pre-line;" class="small">${e.body}</div>
                                </div>
                                <div class="text-muted small mt-1 ${isMe ? 'text-end me-1' : 'text-start ms-1'}" style="font-size: 0.75rem;">
                                    ${new Date(e.created_at).toLocaleString('fr-FR', {day: 'numeric', month: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'})}
                                </div>
                            </div>
                        `;
                        
                        box.appendChild(wrap);
                        box.scrollTop = box.scrollHeight;
                        lastMessageCount++;
                    });
            }

            // Système de secours instantané (Long-Polling à 3,5s)
            setInterval(async () => {
                // S'active uniquement si le WebSocket n'est pas déjà connecté
                if (!window.Echo || window.Echo.connector.pusher.connection.state !== 'connected') {
                    try {
                        const res = await fetch(`/chat/mini/${convId}/messages`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
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