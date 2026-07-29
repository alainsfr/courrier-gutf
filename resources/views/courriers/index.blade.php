<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h1 class="h4 mb-1 text-dark fw-bold">{{ __('courrier.title') }}</h1>
                <p class="text-muted small mb-0">Registre et traçabilité des plis du GUTF</p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                @can('courrier.export')
                    <a class="btn btn-light border border-slate-200 px-3 rounded-3 fw-semibold small d-flex align-items-center gap-2"
                       href="{{ route('courriers.export.excel', request()->query()) }}">
                        <span>📊</span> {{ __('courrier.export_excel') }}
                    </a>
                    <a class="btn btn-light border border-slate-200 px-3 rounded-3 fw-semibold small d-flex align-items-center gap-2"
                       href="{{ route('courriers.export.pdf', request()->query()) }}">
                        <span>📕</span> {{ __('courrier.export_pdf') }}
                    </a>
                @endcan

                @can('courrier.create')
                    <a href="{{ route('courriers.create') }}" class="btn btn-gutf px-4 rounded-3 fw-semibold d-flex align-items-center gap-2">
                        <span>+</span> {{ __('courrier.new') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    {{-- BARRE DE FILTRES ET RECHERCHE --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('courriers.index') }}">
                <div class="row g-3 align-items-end">
                    
                    {{-- Recherche textuelle --}}
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold text-muted small">{{ __('courrier.filters.search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-slate-200 text-muted">🔍</span>
                            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                                   class="form-control border-slate-200"
                                   placeholder="{{ __('courrier.filters.search_placeholder') }}">
                        </div>
                    </div>

                    {{-- Filtrer par Type --}}
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-semibold text-muted small">{{ __('courrier.filters.type') }}</label>
                        <select name="type" class="form-select border-slate-200">
                            <option value="">{{ __('courrier.filters.all') }}</option>
                            <option value="entrant" @selected(($filters['type'] ?? '') === 'entrant')>📥 {{ __('courrier.type_in') }}</option>
                            <option value="sortant" @selected(($filters['type'] ?? '') === 'sortant')>📤 {{ __('courrier.type_out') }}</option>
                        </select>
                    </div>

                    {{-- Filtrer par étape workflow --}}
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-semibold text-muted small">{{ __('courrier.filters.etape') }}</label>
                        <select name="workflow_etape_id" class="form-select border-slate-200">
                            <option value="">{{ __('courrier.filters.all') }}</option>
                            @foreach($etapes as $etape)
                                <option value="{{ $etape->id }}" @selected(($filters['workflow_etape_id'] ?? '') == $etape->id)>
                                    {{ $etape->libelle }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filtrer par Pièces jointes --}}
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-semibold text-muted small">{{ __('courrier.filters.attachments') }}</label>
                        <select name="has_attachments" class="form-select border-slate-200">
                            <option value="">{{ __('courrier.filters.all') }}</option>
                            <option value="yes" @selected(($filters['has_attachments'] ?? '') === 'yes')>📎 {{ __('courrier.filters.attachments_yes') }}</option>
                            <option value="no" @selected(($filters['has_attachments'] ?? '') === 'no')>❌ {{ __('courrier.filters.attachments_no') }}</option>
                        </select>
                    </div>

                    {{-- Filtrer par Assignation --}}
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold text-muted small">{{ __('courrier.filters.assigned_to') }}</label>
                        <select name="assigned_to" class="form-select border-slate-200">
                            <option value="">{{ __('courrier.filters.all') }}</option>
                            @foreach($agentsList as $u)
                                <option value="{{ $u->id }}" @selected(($filters['assigned_to'] ?? '') == $u->id)>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Période de début --}}
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold text-muted small">{{ __('courrier.filters.date_from') }}</label>
                        <input type="date" name="date_from" class="form-control border-slate-200" value="{{ $filters['date_from'] ?? '' }}">
                    </div>

                    {{-- Période de fin --}}
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold text-muted small">{{ __('courrier.filters.date_to') }}</label>
                        <input type="date" name="date_to" class="form-control border-slate-200" value="{{ $filters['date_to'] ?? '' }}">
                    </div>

                    {{-- Périmètre d'accès --}}
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold text-muted small">{{ __('courrier.filters.scope') }}</label>
                        <select name="scope" class="form-select border-slate-200" @if(!$isAdmin) disabled @endif>
                            <option value="mine" @selected(($filters['scope'] ?? 'mine') === 'mine')>
                                🔒 {{ __('courrier.filters.scope_mine') }}
                            </option>
                            <option value="all" @selected(($filters['scope'] ?? 'all') === 'all')>
                                🌍 {{ __('courrier.filters.scope_all') }}
                            </option>
                        </select>
                        @if(!$isAdmin)
                            <input type="hidden" name="scope" value="mine">
                        @endif
                    </div>

                    {{-- Actions de soumission --}}
                    <div class="col-lg-3 col-md-6 d-flex gap-2">
                        <button type="submit" class="btn btn-gutf w-100 py-2 rounded-3 shadow-sm font-semibold small">
                            {{ __('courrier.filters.apply') }}
                        </button>
                        <a href="{{ route('courriers.index') }}" class="btn btn-light border border-slate-200 w-100 py-2 rounded-3 small">
                            {{ __('courrier.filters.reset') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- LISTE DES COURRIERS --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle table-hover">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="ps-4 py-3">{{ __('courrier.ref') }}</th>
                            <th class="py-3">{{ __('courrier.type') }}</th>
                            <th class="py-3">{{ __('courrier.subject') }}</th>
                            <th class="py-3">{{ __('courrier.assigned_to') }}</th>
                            <th class="py-3">{{ __('courrier.filters.etape') }}</th>
                            <th class="pe-4 py-3 text-end">{{ __('courrier.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courriers as $c)
                            <tr style="cursor: pointer;" onclick="window.location='{{ route('courriers.show', $c) }}'">
                                <td class="ps-4 fw-bold text-dark py-3">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span>{{ $c->reference_interne }}</span>
                                        @if(($c->attachments_count ?? 0) > 0)
                                            <span class="badge text-bg-light border small text-muted">📎 {{ $c->attachments_count }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($c->type === 'entrant')
                                        <span class="badge text-bg-light text-primary border">📥 ENTRANT</span>
                                    @elseif($c->type === 'interne')
                                        <span class="badge text-bg-light text-secondary border">🔁 INTERNE</span>
                                    @else
                                        <span class="badge text-bg-light text-warning border">📤 SORTANT</span>
                                    @endif
                                </td>
                                <td class="text-dark fw-semibold">{{ \Illuminate\Support\Str::limit($c->objet, 60) }}</td>
                                <td class="text-muted">
                                    @if($c->assignedTo)
                                        <div class="fw-semibold text-dark">{{ $c->assignedTo->name }}</div>
                                        <div class="small text-muted">{{ $c->assignedTo->email }}</div>
                                    @else
                                        <span class="text-muted small">Non assigné</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge" style="{{ $c->workflowEtape?->ui_color ? 'border-color:'.$c->workflowEtape->ui_color.';color:'.$c->workflowEtape->ui_color : '' }}">
                                        {{ $c->workflowEtape?->libelle ?? '—' }}
                                    </span>
                                </td>
                                <td class="pe-4 text-end" onclick="event.stopPropagation();">
                                    <a class="btn btn-outline-secondary btn-sm px-3 rounded-3 small fw-semibold" href="{{ route('courriers.show', $c) }}">
                                        {{ __('courrier.view') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <div class="fs-2 mb-2">📁</div>
                                    <div>{{ __('courrier.empty') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PAGINATION --}}
    <div class="mt-4">
        {{ $courriers->links() }}
    </div>
</x-app-layout>