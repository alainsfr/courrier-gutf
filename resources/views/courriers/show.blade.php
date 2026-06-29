<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge text-bg-light border small text-muted">Référence</span>
                    <h1 class="h5 mb-0 text-dark fw-bold">{{ $courrier->reference_interne }}</h1>
                </div>
                <p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit($courrier->objet, 100) }}</p>
            </div>
            <a href="{{ route('courriers.index') }}" class="btn btn-outline-secondary px-4 rounded-3 fw-semibold">
                ← {{ __('courrier.back') }}
            </a>
        </div>
    </x-slot>

    @php
        $statusClass = 'status-' . str_replace('-', '_', $courrier->status);
    @endphp

    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-2">
            <span>✅</span> <div>{{ session('status') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <div class="fw-bold mb-2">{{ __('courrier.form_error_title') }}</div>
            <ul class="mb-0 small">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        {{-- COLONNE PRINCIPALE : DÉTAILS, DOCUMENTS ET TIMELINE --}}
        <div class="col-lg-8">
            
            {{-- 1. Informations générales --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-4">📄</span>
                            <h5 class="card-title fw-bold text-dark mb-0">Données du pli</h5>
                        </div>
                        <span class="status-badge {{ $statusClass }} px-3 py-2">
                            {{ __('courrier.status_label.' . $courrier->status) }}
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6 border-bottom pb-2">
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">{{ __('courrier.type') }}</div>
                            <div class="text-dark fw-bold mt-1">
                                @if($courrier->type === 'entrant')
                                    📥 ENTRANT (Pli reçu)
                                @else
                                    📤 SORTANT (Pli envoyé)
                                @endif
                            </div>
                        </div>

                        <div class="col-sm-6 border-bottom pb-2">
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">{{ __('courrier.external_ref') }}</div>
                            <div class="text-dark fw-semibold mt-1">{{ $courrier->reference_externe ?? '—' }}</div>
                        </div>

                        <div class="col-sm-6 border-bottom pb-2">
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">{{ __('courrier.sender') }}</div>
                            <div class="text-dark fw-semibold mt-1">{{ $courrier->expediteur ?? '—' }}</div>
                        </div>

                        <div class="col-sm-6 border-bottom pb-2">
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">{{ __('courrier.recipient') }}</div>
                            <div class="text-dark fw-semibold mt-1">{{ $courrier->destinataire ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="text-muted small fw-semibold text-uppercase mb-2" style="font-size: 0.75rem;">{{ __('courrier.description') }}</div>
                        <div class="bg-light p-3 rounded-3 text-dark style-text" style="white-space: pre-line;">{{ $courrier->description ?? 'Aucune description ou note sur ce pli.' }}</div>
                    </div>
                </div>
            </div>

            {{-- 2. Gestionnaire de Pièces Jointes --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4">📎</span>
                        <h5 class="card-title fw-bold text-dark mb-0">{{ __('courrier.attachments') }}</h5>
                    </div>
                    <span class="badge text-bg-light border px-2 py-1 small text-muted">
                        {{ $courrier->attachments->count() }} document(s)
                    </span>
                </div>

                <div class="card-body p-4">
                    @can('courrier.attach')
                        @if($courrier->status !== 'archive')
                            <form method="POST" action="{{ route('courriers.attachments.upload', $courrier) }}" enctype="multipart/form-data" class="mb-4">
                                @csrf
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-9">
                                        <input type="file" name="file" class="form-control border-slate-200 rounded-3" required>
                                        <div class="form-text small">{{ __('courrier.attachments_help') }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-gutf w-100 py-2 rounded-3 shadow-sm font-semibold small" type="submit">
                                            {{ __('courrier.upload') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    @endcan

                    @if($courrier->attachments->count())
                        <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                            @foreach($courrier->attachments as $att)
                                <div class="list-group-item d-flex align-items-center justify-content-between p-3 flex-wrap gap-2 hover:bg-light">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-3">📄</span>
                                        <div>
                                            <div class="fw-bold text-dark mb-1">{{ $att->original_name }}</div>
                                            <div class="text-muted small" style="font-size: 0.8rem;">
                                                Par {{ $att->uploadedBy?->name ?? 'Système' }} 
                                                · {{ number_format(($att->size ?? 0) / 1024, 0) }} KB 
                                                · {{ $att->created_at->format('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <a class="btn btn-outline-secondary btn-sm px-3 rounded-3 font-semibold"
                                           target="_blank"
                                           href="{{ route('courriers.attachments.preview', [$courrier, $att]) }}">
                                            👁️ {{ __('courrier.preview') }}
                                        </a>

                                        <a class="btn btn-outline-secondary btn-sm px-3 rounded-3 font-semibold"
                                           href="{{ route('courriers.attachments.download', [$courrier, $att]) }}">
                                            📥 {{ __('courrier.download') }}
                                        </a>

                                        @can('courrier.detach')
                                            @if($courrier->status !== 'archive')
                                                <form method="POST" action="{{ route('courriers.attachments.delete', [$courrier, $att]) }}" onsubmit="return confirm('{{ __('courrier.confirm_delete_attachment') }}');" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm px-3 rounded-3 font-semibold">
                                                        🗑️ {{ __('courrier.delete_attachment') }}
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted border border-dashed rounded-3">
                            <div class="fs-1 mb-2">📁</div>
                            <div class="small">{{ __('courrier.no_attachments') }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 3. Historique d'Audit (Timeline) --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4">⏳</span>
                        <h5 class="card-title fw-bold text-dark mb-0">{{ __('courrier.history') }}</h5>
                    </div>
                </div>

                <div class="card-body p-4">
                    @forelse($courrier->histories as $h)
                        @php
                            $labelKey = 'courrier.history_actions.' . $h->action;
                            $iconKey  = 'courrier.history_icons.' . $h->action;

                            $label = __($labelKey);
                            if ($label === $labelKey) { $label = $h->action; }

                            $icon  = __($iconKey);
                            if ($icon === $iconKey) { $icon = '•'; }
                        @endphp

                        <ul class="timeline">
                            <li class="timeline-item pb-4">
                                <span class="timeline-dot"></span>

                                <div class="timeline-meta small text-muted">
                                    {{ $h->created_at->format('d/m/Y H:i') }} — par <strong>{{ $h->actor?->name ?? 'Système' }}</strong>
                                </div>

                                <div class="timeline-title fw-bold text-dark mt-1">
                                    {{ $icon }} {{ $label }}
                                </div>

                                @if($h->from_status || $h->to_status)
                                    <div class="text-muted small mt-1">
                                        {{ $h->from_status ? __('courrier.status_label.' . $h->from_status) : 'Début' }}
                                        ➔ 
                                        <strong>{{ $h->to_status ? __('courrier.status_label.' . $h->to_status) : '—' }}</strong>
                                    </div>
                                @endif

                                @if($h->metadata)
                                    <div class="timeline-box bg-light border-0 rounded-3 p-3 mt-2">
                                        @foreach($h->metadata as $k => $v)
                                            @php
                                                $metaLabelKey = 'courrier.history_meta.' . $k;
                                                $metaLabel = __($metaLabelKey);
                                                if ($metaLabel === $metaLabelKey) {
                                                    $metaLabel = ucfirst(str_replace('_', ' ', (string) $k));
                                                }
                                            @endphp
                                            <div class="small text-dark mb-1">
                                                <strong>{{ $metaLabel }} :</strong> {{ $v }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                        </ul>
                    @empty
                        <div class="text-center py-4 text-muted border border-dashed rounded-3">
                            <div class="small">{{ __('courrier.no_history') }}</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- COLONNE LATÉRALE : ACTIONS INTELLIGENTES DU WORKFLOW --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 position-sticky" style="top: 24px;">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4">⚡</span>
                        <h5 class="card-title fw-bold text-dark mb-0">Workflow</h5>
                    </div>
                </div>

                <div class="card-body p-4 d-grid gap-3">
                    
                    {{-- Action d'Affectation (Spatie Permission) --}}
                    @can('courrier.assign')
                        @if($courrier->status !== 'archive')
                            <form method="POST" action="{{ route('courriers.assign', $courrier) }}" class="border rounded-3 p-3 bg-light">
                                @csrf
                                <label class="form-label fw-bold text-dark small mb-2">{{ __('courrier.assign_to') }}</label>
                                <select name="assigned_to_user_id" class="form-select border-slate-200 rounded-3 mb-3" required>
                                    <option value="" disabled selected>Choisir un collaborateur</option>
                                    @foreach($agents as $a)
                                        <option value="{{ $a->id }}" @selected($courrier->assigned_to_user_id == $a->id)>
                                            {{ $a->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-gutf w-100 py-2 rounded-3 shadow-sm font-semibold small" type="submit">
                                    📌 {{ __('courrier.assign') }}
                                </button>
                            </form>
                        @endif
                    @endcan

                    {{-- Action Début Traitement --}}
                    @can('courrier.process')
                        @if($courrier->status === 'affecte')
                            <form method="POST" action="{{ route('courriers.start_processing', $courrier) }}">
                                @csrf
                                <button class="btn btn-outline-primary py-3 w-100 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2" type="submit">
                                    <span>⚙️</span> {{ __('courrier.start_processing') }}
                                </button>
                            </form>
                        @endif
                    @endcan

                    {{-- Action Soumission Validation --}}
                    @can('courrier.process')
                        @if(in_array($courrier->status, ['affecte', 'en_traitement']))
                            <form method="POST" action="{{ route('courriers.submit_validation', $courrier) }}">
                                @csrf
                                <button class="btn btn-primary py-3 w-100 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2" type="submit">
                                    <span>📤</span> {{ __('courrier.submit_validation') }}
                                </button>
                            </form>
                        @endif
                    @endcan

                    {{-- Actions de Validation/Rejet (Pour les Validateurs) --}}
                    @can('courrier.validate')
                        @if($courrier->status === 'en_validation')
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="fw-bold text-dark small mb-3">Décision du validateur</div>
                                
                                <form method="POST" action="{{ route('courriers.validate', $courrier) }}" class="mb-3">
                                    @csrf
                                    <button class="btn btn-success w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2" type="submit">
                                        <span>✅</span> {{ __('courrier.validate') }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('courriers.reject', $courrier) }}">
                                    @csrf
                                    <label class="form-label text-muted small fw-semibold">{{ __('courrier.reject_reason') }}</label>
                                    <textarea name="reason" class="form-control border-slate-200 rounded-3 mb-2" rows="2" required placeholder="Saisir la raison du rejet..."></textarea>
                                    <button class="btn btn-outline-danger w-100 py-2 rounded-3 font-semibold" type="submit">
                                        ❌ {{ __('courrier.reject') }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endcan

                    {{-- Action Archivage --}}
                    @can('courrier.archive')
                        @if(in_array($courrier->status, ['valide', 'rejete']))
                            <form method="POST" action="{{ route('courriers.archive', $courrier) }}">
                                @csrf
                                <button class="btn btn-outline-dark py-3 w-100 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2" type="submit">
                                    <span>📦</span> {{ __('courrier.archive') }}
                                </button>
                            </form>
                        @endif
                    @endcan

                    {{-- État final clos (Archivé) --}}
                    @if($courrier->status === 'archive')
                        <div class="text-center py-4 text-muted bg-light border border-slate-200 rounded-3">
                            <div class="fs-1 mb-1">📦</div>
                            <div class="fw-bold">Dossier Archivé</div>
                            <div class="small">Ce pli est clos et archivé définitivement.</div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>