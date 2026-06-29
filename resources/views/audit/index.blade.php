<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between">
            <h1 class="h4">{{ __('audit.title') }}</h1>
            <div>
                <a class="btn btn-outline-secondary" href="{{ route('audit.export.excel') }}">Excel</a>
                <a class="btn btn-outline-secondary" href="{{ route('audit.export.pdf') }}">PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input name="q" value="{{ $q }}" class="form-control" placeholder="Référence courrier">
                </div>
                <div class="col-md-3">
                    <select name="action" class="form-select">
                        <option value="">Action</option>
                        @foreach(__('audit.actions') as $key => $label)
                            <option value="{{ $key }}" @selected($action === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Courrier</th>
                    <th>Action</th>
                    <th>Acteur</th>
                    <th>Détails</th>
                </tr>
                </thead>
                <tbody>
                @foreach($histories as $h)
                    <tr>
                        <td>{{ $h->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $h->courrier->reference_interne }}</td>
                        <td>{{ __('audit.actions.' . $h->action) }}</td>
                        <td>{{ $h->actor?->name }}</td>
                        <td>
                            @if($h->metadata)
                                <pre class="mb-0 small">{{ json_encode($h->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{ $histories->links() }}
</x-app-layout>
