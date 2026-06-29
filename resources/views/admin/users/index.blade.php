<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h1 class="h4 mb-1 text-dark fw-bold">{{ __('users.title') }}</h1>
                <p class="text-muted small mb-0">Gestion et attribution des accès sur la plateforme du GUTF</p>
            </div>
            <a class="btn btn-gutf px-4 rounded-3 fw-semibold d-flex align-items-center gap-2" href="{{ route('admin.users.create') }}">
                <span>+</span> {{ __('users.new') }}
            </a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-2">
            <span>✅</span> <div>{{ session('status') }}</div>
        </div>
    @endif

    {{-- BARRE DE RECHERCHE --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.users.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold text-muted small">{{ __('users.search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-slate-200 text-muted">🔍</span>
                            <input name="q" value="{{ $q }}" class="form-control border-slate-200" placeholder="{{ __('users.search_placeholder') }}">
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <button class="btn btn-outline-secondary w-100 py-2 rounded-3 small font-semibold" type="submit">
                            {{ __('users.apply') }}
                        </button>
                    </div>
                    <div class="col-md-2 col-6">
                        <a class="btn btn-light border border-slate-200 w-100 py-2 rounded-3 small text-center d-block text-decoration-none" href="{{ route('admin.users.index') }}">
                            {{ __('users.reset') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLEAU DES UTILISATEURS --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle table-hover">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="ps-4 py-3">{{ __('users.name') }}</th>
                            <th class="py-3">{{ __('users.email') }}</th>
                            <th class="py-3">{{ __('users.role') }}</th>
                            <th class="py-3">{{ __('users.locale') }}</th>
                            <th class="py-3">{{ __('users.status') }}</th>
                            <th class="pe-4 py-3 text-end">{{ __('users.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-4">👤</span>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $u->name }}</div>
                                            <div class="small text-muted">ID: #{{ $u->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-dark fw-semibold">{{ $u->email }}</td>
                                <td>
                                    <span class="badge text-bg-light border text-dark text-capitalize px-3 py-2 rounded-3 small">
                                        ⚙️ {{ str_replace('_', ' ', $u->getRoleNames()->first() ?? 'Aucun') }}
                                    </span>
                                </td>
                                <td class="text-muted fw-bold">{{ strtoupper($u->locale ?? 'fr') }}</td>
                                <td>
                                    @if($u->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill small fw-bold">
                                            ● {{ __('users.active') }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill small fw-bold">
                                            ● {{ __('users.inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <a class="btn btn-outline-secondary btn-sm px-3 rounded-3 fw-semibold small" href="{{ route('admin.users.edit', $u) }}">
                                            {{ __('users.edit') }}
                                        </a>
                                        <form method="POST" action="{{ route('admin.users.toggle_active', $u) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-outline-dark btn-sm px-3 rounded-3 fw-semibold small" type="submit">
                                                {{ $u->is_active ? __('users.deactivate') : __('users.activate') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <div class="fs-1 mb-2">👥</div>
                                    <div>{{ __('users.empty') }}</div>
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
        {{ $users->links() }}
    </div>
</x-app-layout>