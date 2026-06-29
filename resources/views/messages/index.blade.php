<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <h1 class="h4 mb-0">Messagerie</h1>
        </div>
    </x-slot>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>Conversations</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('messages.direct') }}" class="mb-3">
                        @csrf
                        <label class="form-label">Nouveau message</label>
                        <select name="user_id" class="form-select" required>
                            @foreach($users as $u)
                                @if($u->id !== auth()->id())
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endif
                            @endforeach
                        </select>
                        <button class="btn btn-outline-secondary w-100 mt-2" type="submit">Démarrer</button>
                    </form>

                    @can('chat.group.create')
                        <form method="POST" action="{{ route('messages.group.create') }}">
                            @csrf
                            <label class="form-label">Nouveau groupe</label>
                            <input class="form-control mb-2" name="title" placeholder="Nom du groupe" required>
                            <select name="members[]" class="form-select" multiple required style="height:120px;">
                                @foreach($users as $u)
                                    @if($u->id !== auth()->id())
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text">Ctrl/⌘ pour sélection multiple</div>
                            <button class="btn btn-outline-secondary w-100 mt-2" type="submit">Créer</button>
                        </form>
                    @endcan

                    <hr>

                    <div class="list-group">
                        @foreach($conversations as $c)
                            <a class="list-group-item list-group-item-action"
                               href="{{ route('messages.show', $c) }}">
                                <div class="fw-semibold">
                                    {{ $c->type === 'group' ? ($c->title ?? 'Groupe') : 'Discussion' }}
                                </div>
                                <div class="text-muted small">
                                    @php $last = $c->messages->first(); @endphp
                                    {{ $last?->body ? \Illuminate\Support\Str::limit($last->body, 45) : '—' }}
                                </div>
                            </a>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body text-muted">
                    Sélectionnez une conversation à gauche.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
