<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h1 class="h4 mb-0">{{ __('courrier.title') }}</h1>

            <div class="d-flex align-items-center gap-2">
                @can('courrier.export')
                    <a class="btn btn-outline-secondary"
                    href="{{ route('courriers.export.excel', request()->query()) }}">
                        {{ __('courrier.export_excel') }}
                    </a>
                    <a class="btn btn-outline-secondary"
                    href="{{ route('courriers.export.pdf', request()->query()) }}">
                        {{ __('courrier.export_pdf') }}
                    </a>
                @endcan

                @can('courrier.create')
                    <a href="{{ route('courriers.create') }}" class="btn btn-gutf">
                        {{ __('courrier.new') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('courriers.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3">
                        <label class="form-label">{{ __('courrier.filters.search') }}</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                               class="form-control"
                               placeholder="{{ __('courrier.filters.search_placeholder') }}">
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label">{{ __('courrier.filters.type') }}</label>
                        <select name="type" class="form-select">
                            <option value="">{{ __('courrier.filters.all') }}</option>
                            <option value="entrant" @selected(($filters['type'] ?? '') === 'entrant')>{{ __('courrier.type_in') }}</option>
                            <option value="sortant" @selected(($filters['type'] ?? '') === 'sortant')>{{ __('courrier.type_out') }}</option>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label">{{ __('courrier.filters.status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('courrier.filters.all') }}</option>
                            @foreach($allowedStatuses as $st)
                                <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>
                                    {{ __('courrier.status_label.' . $st) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label">{{ __('courrier.filters.attachments') }}</label>
                        <select name="has_attachments" class="form-select">
                            <option value="">{{ __('courrier.filters.all') }}</option>
                            <option value="yes" @selected(($filters['has_attachments'] ?? '') === 'yes')>{{ __('courrier.filters.attachments_yes') }}</option>
                            <option value="no" @selected(($filters['has_attachments'] ?? '') === 'no')>{{ __('courrier.filters.attachments_no') }}</option>
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">{{ __('courrier.filters.assigned_to') }}</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">{{ __('courrier.filters.all') }}</option>
                            @foreach($agentsList as $u)
                                <option value="{{ $u->id }}" @selected(($filters['assigned_to'] ?? '') == $u->id)>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">{{ __('courrier.filters.date_from') }}</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">{{ __('courrier.filters.date_to') }}</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">{{ __('courrier.filters.scope') }}</label>
                        <select name="scope" class="form-select" @if(!$isAdmin) disabled @endif>
                            <option value="mine" @selected(($filters['scope'] ?? 'mine') === 'mine')>
                                {{ __('courrier.filters.scope_mine') }}
                            </option>
                            <option value="all" @selected(($filters['scope'] ?? 'all') === 'all')>
                                {{ __('courrier.filters.scope_all') }}
                            </option>
                        </select>
                        @if(!$isAdmin)
                            <input type="hidden" name="scope" value="mine">
                        @endif
                    </div>

                    <div class="col-12 d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-outline-secondary">
                            {{ __('courrier.filters.apply') }}
                        </button>
                        <a href="{{ route('courriers.index') }}" class="btn btn-outline-danger">
                            {{ __('courrier.filters.reset') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('courrier.ref') }}</th>
                            <th>{{ __('courrier.type') }}</th>
                            <th>{{ __('courrier.subject') }}</th>
                            <th>{{ __('courrier.assigned_to') }}</th>
                            <th>{{ __('courrier.status') }}</th>
                            <th class="text-end">{{ __('courrier.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courriers as $c)
                            @php
                                $statusClass = 'status-' . str_replace('-', '_', $c->status);
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span>{{ $c->reference_interne }}</span>
                                        @if(($c->attachments_count ?? 0) > 0)
                                            <span class="badge text-bg-secondary">📎 {{ $c->attachments_count }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ strtoupper($c->type) }}</td>
                                <td>{{ $c->objet }}</td>
                                <td class="text-muted">
                                    @if($c->assignedTo)
                                        {{ $c->assignedTo->name }}
                                        <div class="small">{{ $c->assignedTo->email }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge {{ $statusClass }}">
                                        {{ __('courrier.status_label.' . $c->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('courriers.show', $c) }}">
                                        {{ __('courrier.view') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    {{ __('courrier.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $courriers->links() }}
    </div>
</x-app-layout>
