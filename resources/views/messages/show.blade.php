<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1 class="h5 mb-0">
                    {{ $conversation->type === 'group' ? ($conversation->title ?? 'Groupe') : 'Discussion' }}
                </h1>
                <div class="text-muted small">
                    Membres : {{ $conversation->participants->count() }}
                </div>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('messages.index') }}">Retour</a>
        </div>
    </x-slot>

    <div class="card shadow-sm">
        <div class="card-body" style="height:420px; overflow:auto;" id="messagesBox">
            @foreach($conversation->messages as $m)
                <div class="mb-2">
                    <div class="small text-muted">
                        {{ $m->sender?->name }} — {{ $m->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div class="border rounded p-2 bg-white">
                        {{ $m->body }}
                    </div>
                </div>
            @endforeach
        </div>

        @can('chat.send')
            <div class="card-footer bg-white">
                <form method="POST" action="{{ route('messages.send', $conversation) }}" id="sendForm">
                    @csrf
                    <div class="d-flex gap-2">
                        <input class="form-control" name="body" id="msgInput" placeholder="Écrire un message..." required maxlength="2000">
                        <button class="btn btn-gutf" type="submit">Envoyer</button>
                    </div>
                </form>
            </div>
        @endcan
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const convId = {{ (int) $conversation->id }};
            const box = document.getElementById('messagesBox');
            const input = document.getElementById('msgInput');

            if (box) box.scrollTop = box.scrollHeight;

            if (window.Echo) {
                window.Echo.private(`conversations.${convId}`)
                    .listen('.message.sent', (e) => {
                        const wrap = document.createElement('div');
                        wrap.className = 'mb-2';
                        wrap.innerHTML = `
                            <div class="small text-muted">${e.sender.name} — ${new Date(e.created_at).toLocaleString()}</div>
                            <div class="border rounded p-2 bg-white">${e.body}</div>
                        `;
                        box.appendChild(wrap);
                        box.scrollTop = box.scrollHeight;
                    });
            }
        });
    </script>
</x-app-layout>
