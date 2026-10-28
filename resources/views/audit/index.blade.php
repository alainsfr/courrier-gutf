@php
    $field = 'ui-input';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-bold text-slate-900">{{ __("Journal d'audit") }}</h1>
            <div class="flex flex-wrap gap-2">
                <x-buttons.secondary :href="route('audit.export.excel')">Excel</x-buttons.secondary>
                <x-buttons.secondary :href="route('audit.export.pdf')">PDF</x-buttons.secondary>
            </div>
        </div>
    </x-slot>

    <div class="mb-6 ui-card p-5 shadow-sm">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
            <div>
                <label class="ui-label" for="audit-q">{{ __('audit.reference') }}</label>
                <input id="audit-q" name="q" value="{{ $q }}" class="{{ $field }}" placeholder="{{ __('audit.reference') }}">
            </div>
            <div>
                <label class="ui-label" for="audit-action">{{ __('audit.action') }}</label>
                <select id="audit-action" name="action" class="{{ $field }}">
                    <option value="">Action</option>
                    @foreach(__('audit.actions') as $key => $label)
                        <option value="{{ $key }}" @selected($action === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ui-label" for="audit-from">{{ __('audit.date_from') }}</label>
                <input id="audit-from" type="date" name="date_from" value="{{ $dateFrom }}" class="{{ $field }}">
            </div>
            <div>
                <label class="ui-label" for="audit-to">{{ __('audit.date_to') }}</label>
                <input id="audit-to" type="date" name="date_to" value="{{ $dateTo }}" class="{{ $field }}">
            </div>
            <div>
                <x-buttons.primary type="submit" class="w-full">Filtrer</x-buttons.primary>
            </div>
        </form>
    </div>

    <div class="overflow-hidden ui-card shadow-sm">
        <div class="overflow-x-auto ui-table-scroll">
            <table class="ui-table min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Courrier</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Acteur</th>
                        <th class="px-4 py-3">Détails</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($histories as $h)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 text-slate-600">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $h->courrier->reference_interne }}</td>
                            <td class="px-4 py-3">{{ __('audit.actions.' . $h->action) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $h->actor?->name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                @if($h->metadata)
                                    <pre class="mb-0 whitespace-pre-wrap">{{ json_encode($h->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4">
                                <x-empty-state title="Aucun événement d'audit." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $histories->links() }}</div>
</x-app-layout>
