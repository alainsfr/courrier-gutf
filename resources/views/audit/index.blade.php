@php
    $field = 'w-full border border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-1';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-bold text-gray-900">{{ __("Journal d'audit") }}</h1>
            <div class="flex flex-wrap gap-2">
                <x-buttons.secondary :href="route('audit.export.excel')">Excel</x-buttons.secondary>
                <x-buttons.secondary :href="route('audit.export.pdf')">PDF</x-buttons.secondary>
            </div>
        </div>
    </x-slot>

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
            <div>
                <input name="q" value="{{ $q }}" class="{{ $field }}" placeholder="Référence courrier">
            </div>
            <div>
                <select name="action" class="{{ $field }}">
                    <option value="">Action</option>
                    @foreach(__('audit.actions') as $key => $label)
                        <option value="{{ $key }}" @selected($action === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="{{ $field }}">
            </div>
            <div>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="{{ $field }}">
            </div>
            <div>
                <x-buttons.primary type="submit" class="w-full">Filtrer</x-buttons.primary>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Courrier</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Acteur</th>
                        <th class="px-4 py-3">Détails</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($histories as $h)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 text-gray-600">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $h->courrier->reference_interne }}</td>
                            <td class="px-4 py-3">{{ __('audit.actions.' . $h->action) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $h->actor?->name }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">
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
