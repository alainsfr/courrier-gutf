<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="h6 mb-0">{{ $courrier->reference_interne }}</div>
                <div class="text-muted small">{{ $courrier->objet }}</div>
            </div>
            <a href="{{ route('courriers.index') }}" class="btn btn-outline-secondary">
                {{ __('courrier.back') }}
            </a>
        </div>
    </x-slot>

    @php
        $statusClass = 'status-' . str_replace('-', '_', $courrier->status);
    @endphp

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">{{ __('courrier.form_error_title') }}</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Informations courrier --}}
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted small">{{ __('courrier.status') }}</div>
                        <span class="status-badge {{ $statusClass }}">
                            {{ __('courrier.status_label.' . $courrier->status) }}
                        </span>
                    </div>

                    <hr class="my-3">

                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">{{ __('courrier.type') }}</dt>
                        <dd class="col-sm-8">{{ strtoupper($courrier->type) }}</dd>

                        <dt class="col-sm-4 text-muted">{{ __('courrier.external_ref') }}</dt>
                        <dd class="col-sm-8">{{ $courrier->reference_externe ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted">{{ __('courrier.sender') }}</dt>
                        <dd class="col-sm-8">{{ $courrier->expediteur ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted">{{ __('courrier.recipient') }}</dt>
                        <dd class="col-sm-8">{{ $courrier->destinataire ?? '—' }}</dd>
                    </dl>

                    <hr class="my-3">

                    <div class="text-muted small mb-1">{{ __('courrier.description') }}</div>
                    <div>{{ $courrier->description ?? '—' }}</div>
                </div>
            </div>

            {{-- PIÈCES JOINTES --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <strong>{{ __('courrier.attachments') }}</strong>
                    <span class="badge text-bg-secondary">
                        📎 {{ $courrier->attachments->count() }}
                    </span>
                </div>

                <div class="card-body">
                    @can('courrier.attach')
                        <form method="POST"
                              action="{{ route('courriers.attachments.upload', $courrier) }}"
                              enctype="multipart/form-data"
                              class="mb-3">
                            @csrf
                            <div class="row g-2 align-items-center">
                                <div class="col-md-8">
                                    <input type="file" name="file" class="form-control" required>
                                    <div class="form-text">{{ __('courrier.attachments_help') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-gutf w-100" type="submit">
                                        {{ __('courrier.upload') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endcan

                    @if($courrier->attachments->count())
                        <div class="list-group">
                            @foreach($courrier->attachments as $att)
                                <div class="list-group-item d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $att->original_name }}</div>
                                        <div class="text-muted small">
                                            {{ strtoupper($att->source) }}
                                            · {{ number_format(($att->size ?? 0) / 1024, 0) }} KB
                                            · {{ $att->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <a class="btn btn-outline-secondary btn-sm"
                                           target="_blank"
                                           href="{{ route('courriers.attachments.preview', [$courrier, $att]) }}">
                                            {{ __('courrier.preview') }}
                                        </a>

                                        <a class="btn btn-outline-secondary btn-sm"
                                           href="{{ route('courriers.attachments.download', [$courrier, $att]) }}">
                                            {{ __('courrier.download') }}
                                        </a>

                                        @can('courrier.detach')
                                            <form method="POST"
                                                  action="{{ route('courriers.attachments.delete', [$courrier, $att]) }}"
                                                  onsubmit="return confirm('{{ __('courrier.confirm_delete_attachment') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    {{ __('courrier.delete_attachment') }}
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted">{{ __('courrier.no_attachments') }}</div>
                    @endif
                </div>
            </div>

            {{-- HISTORIQUE (timeline) --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <strong>{{ __('courrier.history') }}</strong>
                </div>

                <div class="card-body">
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
                            <li class="timeline-item">
                                <span class="timeline-dot"></span>

                                <div class="timeline-meta">
                                    {{ $h->created_at->format('d/m/Y H:i') }} — {{ $h->actor?->name }}
                                </div>

                                <div class="timeline-title">
                                    {{ $icon }} {{ $label }}
                                </div>

                                @if($h->from_status || $h->to_status)
                                    <div class="timeline-meta">
                                        {{ $h->from_status ? __('courrier.status_label.' . $h->from_status) : '—' }}
                                        →
                                        {{ $h->to_status ? __('courrier.status_label.' . $h->to_status) : '—' }}
                                    </div>
                                @endif

                                @if($h->metadata)
                                    <div class="timeline-box">
                                        @foreach($h->metadata as $k => $v)
                                            @php
                                                $metaLabelKey = 'courrier.history_meta.' . $k;
                                                $metaLabel = __($metaLabelKey);
                                                if ($metaLabel === $metaLabelKey) {
                                                    $metaLabel = ucfirst(str_replace('_', ' ', (string) $k));
                                                }
                                            @endphp
                                            <div class="small">
                                                <strong>{{ $metaLabel }} :</strong> {{ $v }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                        </ul>
                    @empty
                        <div class="text-muted">{{ __('courrier.no_history') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- COLONNE ACTIONS --}}
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>{{ __('courrier.actions') }}</strong>
                </div>
                <div class="card-body d-grid gap-2">

                    @can('courrier.assign')
                        <form method="POST" action="{{ route('courriers.assign', $courrier) }}">
                            @csrf
                            <label class="form-label">{{ __('courrier.assign_to') }}</label>
                            <select name="assigned_to_user_id" class="form-select mb-2" required>
                                @foreach($agents as $a)
                                    <option value="{{ $a->id }}" @selected($courrier->assigned_to_user_id == $a->id)>
                                        {{ $a->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-outline-secondary w-100" type="submit">
                                {{ __('courrier.assign') }}
                            </button>
                        </form>
                    @endcan

                    @can('courrier.process')
                        <form method="POST" action="{{ route('courriers.submit_validation', $courrier) }}">
                            @csrf
                            <button class="btn btn-gutf w-100" type="submit">
                                {{ __('courrier.submit_validation') }}
                            </button>
                        </form>
                    @endcan

                    @can('courrier.validate')
                        <form method="POST" action="{{ route('courriers.validate', $courrier) }}">
                            @csrf
                            <button class="btn btn-success w-100" type="submit">
                                {{ __('courrier.validate') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('courriers.reject', $courrier) }}">
                            @csrf
                            <label class="form-label mt-2">{{ __('courrier.reject_reason') }}</label>
                            <textarea name="reason" class="form-control mb-2" rows="2" required></textarea>
                            <button class="btn btn-outline-danger w-100" type="submit">
                                {{ __('courrier.reject') }}
                            </button>
                        </form>
                    @endcan

                    @can('courrier.archive')
                        <form method="POST" action="{{ route('courriers.archive', $courrier) }}">
                            @csrf
                            <button class="btn btn-outline-dark w-100" type="submit">
                                {{ __('courrier.archive') }}
                            </button>
                        </form>
                    @endcan

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
