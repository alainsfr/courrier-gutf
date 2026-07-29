<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1 text-dark fw-bold">{{ __('app.dashboard') }}</h1>
                <p class="text-muted small mb-0">Vue d'ensemble de vos activités et de vos indicateurs</p>
            </div>
            <div class="text-muted small bg-white px-3 py-2 rounded-3 border fw-semibold">
                📅 {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            {{ session('status') }}
        </div>
    @endif

    {{-- CARDS DES INDICATEURS CLÉS (KPIs) --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 overflow-hidden" style="border-left: 5px solid var(--gutf-primary) !important;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.75rem;">{{ __('app.kpi_incoming') }}</div>
                        <div class="h2 fw-bold text-dark mt-2 mb-0">{{ $kpiIncoming }}</div>
                    </div>
                    <span class="fs-1 opacity-75">📥</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 overflow-hidden" style="border-left: 5px solid var(--gutf-accent) !important;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.75rem;">{{ __('app.kpi_in_progress') }}</div>
                        <div class="h2 fw-bold text-dark mt-2 mb-0">{{ $kpiInProgress }}</div>
                    </div>
                    <span class="fs-1 opacity-75">⚙️</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 overflow-hidden" style="border-left: 5px solid #22c55e !important;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.75rem;">{{ __('app.kpi_archived') }}</div>
                        <div class="h2 fw-bold text-dark mt-2 mb-0">{{ $kpiArchived }}</div>
                    </div>
                    <span class="fs-1 opacity-75">📦</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- SECTION PRINCIPALE : COURRIERS RÉCENTS --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold text-dark mb-0">📨 Courriers récents nécessitant une attention</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                                <tr class="small text-muted" style="font-size: 0.8rem;">
                                    <th class="ps-4 py-3">Référence</th>
                                    <th class="py-3">Objet</th>
                                    <th class="py-3">Assigné à</th>
                                    <th class="pe-4 py-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentCourriers as $c)
                                    <tr>
                                        <td class="ps-4 py-3 fw-bold text-dark">{{ $c->reference_interne }}</td>
                                        <td class="text-dark fw-semibold">{{ \Illuminate\Support\Str::limit($c->objet, 45) }}</td>
                                        <td class="text-muted small">
                                            {{ $c->assignedTo ? $c->assignedTo->name : 'Non assigné' }}
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="{{ route('courriers.show', $c) }}" class="btn btn-outline-secondary btn-sm rounded-3 fw-bold">
                                                Voir
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <div class="fs-1 mb-2">📁</div>
                                            <div class="small">Aucun courrier en attente d'action.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION LATÉRALE : RACCOURCIS D'ACTIONS RAPIDES --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold text-dark mb-0">⚡ Raccourcis rapides</h5>
                </div>
                <div class="card-body p-4 d-grid gap-3">
                    
                    @can('courrier.create')
                        <a href="{{ route('courriers.create') }}" class="btn btn-gutf py-3 rounded-3 d-flex align-items-center justify-content-center gap-2 shadow-sm">
                            <span>📝</span> Enregistrer un courrier
                        </a>
                    @endcan

                    <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary py-3 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2">
                        <span>💬</span> Ouvrir la messagerie
                    </a>

                    @can('user.manage')
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light border border-slate-200 py-3 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 text-dark">
                            <span>👥</span> Gérer les utilisateurs
                        </a>
                    @endcan

                </div>
            </div>
        </div>
    </div>
</x-app-layout>