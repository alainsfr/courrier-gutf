<x-app-layout>
    <div class="row g-4" style="height: calc(100vh - 180px); min-height: 500px;">
        
        {{-- COLONNE GAUCHE : NAVIGATION & NOUVELLES DISCUSSIONS (1/3) --}}
        <div class="col-lg-4 d-flex flex-column h-100">
            <div class="card border-0 shadow-sm rounded-4 flex-grow-1 overflow-hidden d-flex flex-column">
                <div class="card-header bg-white p-4 border-bottom">
                    <h5 class="fw-bold text-dark mb-0">💬 Discussions</h5>
                </div>
                
                <div class="card-body p-4 overflow-y-auto" style="flex: 1;">
                    
                    {{-- 1. Nouvelle discussion directe --}}
                    <div class="border rounded-3 p-3 bg-light mb-4">
                        <form method="POST" action="{{ route('messages.direct') }}">
                            @csrf
                            <label class="form-label fw-bold text-dark small mb-2">Nouvelle discussion directe</label>
                            <select name="user_id" class="form-select border-slate-200 rounded-3 mb-2" required>
                                <option value="" disabled selected>Choisir un collaborateur</option>
                                @foreach($users as $u)
                                    @if($u->id !== auth()->id())
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <button class="btn btn-gutf w-100 py-2 rounded-3 small font-semibold" type="submit">Démarrer</button>
                        </form>
                    </div>

                    {{-- 2. Nouveau groupe collaboratif --}}
                    @can('chat.group.create')
                        <div class="border rounded-3 p-3 bg-light mb-4">
                            <form method="POST" action="{{ route('messages.group.create') }}">
                                @csrf
                                <label class="form-label fw-bold text-dark small mb-2">Créer un groupe de travail</label>
                                <input class="form-control border-slate-200 rounded-3 mb-2" name="title" placeholder="Nom du groupe (ex: Validation Transcription)" required>
                                <select name="members[]" class="form-select border-slate-200 rounded-3" multiple required style="height:100px;">
                                    @foreach($users as $u)
                                        @if($u->id !== auth()->id())
                                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="form-text small mt-1 mb-2">Maintenez Ctrl/⌘ pour sélectionner plusieurs membres.</div>
                                <button class="btn btn-outline-secondary w-100 py-2 rounded-3 small font-semibold" type="submit">Créer le groupe</button>
                            </form>
                        </div>
                    @endcan

                    <hr class="my-3">

                    {{-- 3. Liste des conversations actives --}}
                    <div class="list-group list-group-flush">
                        @forelse($conversations as $c)
                            <a class="list-group-item list-group-item-action border rounded-3 p-3 mb-2 d-flex align-items-center justify-content-between hover:bg-light"
                               href="{{ route('messages.show', $c) }}">
                                <div style="min-width: 0; flex: 1;">
                                    <div class="fw-bold text-dark text-truncate">
                                        {{ $c->type === 'group' ? '👥 ' . ($c->title ?? 'Groupe') : '👤 Discussion directe' }}
                                    </div>
                                    <div class="text-muted small text-truncate mt-1">
                                        @php $last = $c->messages->first(); @endphp
                                        {{ $last?->body ? \Illuminate\Support\Str::limit($last->body, 40) : 'Aucun message pour le moment.' }}
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-4 text-muted small">
                                Aucun historique de conversation.
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>

        {{-- COLONNE DROITE : ESPACE DE TRAVAIL VIDE (2/3) --}}
        <div class="col-lg-8 h-100 d-none d-lg-block">
            <div class="card border-0 shadow-sm rounded-4 h-100 d-flex align-items-center justify-content-center text-center p-5 bg-white">
                <div class="p-4 rounded-circle bg-light mb-3" style="font-size: 3rem; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                    💬
                </div>
                <h5 class="fw-bold text-dark">Messagerie interne du GUTF</h5>
                <p class="text-muted small mx-auto" style="max-width: 400px;">
                    Sélectionnez une discussion existante sur le panneau de gauche ou démarrez un nouvel échange pour collaborer avec vos collègues en temps réel.
                </p>
            </div>
        </div>

    </div>
</x-app-layout>