@php
    $field = 'ui-input';
    $label = 'ui-label';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="mb-1 text-lg font-bold text-slate-900">{{ __('courrier.search_title') }}</h1>
                <p class="mb-0 text-sm text-slate-500">{{ __('courrier.search_subtitle') }}</p>
            </div>
            <x-buttons.secondary :href="route('courriers.kanban')">
                {{ __('Espace de Traitement') }}
            </x-buttons.secondary>
        </div>
    </x-slot>

    {{-- Formulaire multicritère --}}
    <form method="GET" action="{{ route('courriers.search') }}"
          class="mb-6 ui-card p-6 shadow-sm">
        <input type="hidden" name="search" value="1">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="{{ $label }}" for="ui-tf">{{ __('courrier.search_tf') }}</label>
                <input id="ui-tf" type="text" name="tf" value="{{ $filters['tf'] }}"
                       placeholder="{{ __('courrier.search_tf_placeholder') }}"
                       class="{{ $field }}">
            </div>

            <div>
                <label class="{{ $label }}" for="ui-type">{{ __('courrier.filters.type') }}</label>
                <select id="ui-type" name="type" class="{{ $field }}">
                    <option value="">{{ __('courrier.filters.all') }}</option>
                    <option value="entrant" @selected($filters['type'] === 'entrant')>{{ __('courrier.type_in') }}</option>
                    <option value="sortant" @selected($filters['type'] === 'sortant')>{{ __('courrier.type_out') }}</option>
                    <option value="interne" @selected($filters['type'] === 'interne')>{{ __('courrier.type_internal') }}</option>
                </select>
            </div>

            <div>
                <label class="{{ $label }}" for="ui-bureau_actuel_id">{{ __('courrier.show_bureau') }}</label>
                <select id="ui-bureau_actuel_id" name="bureau_actuel_id" class="{{ $field }}">
                    <option value="">{{ __('courrier.filters.all') }}</option>
                    @foreach($bureaux as $bureau)
                        <option value="{{ $bureau->id }}" @selected($filters['bureau_actuel_id'] == $bureau->id)>
                            {{ $bureau->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $label }}" for="ui-date_from">{{ __('courrier.filters.date_from') }}</label>
                <input id="ui-date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="{{ $field }}">
            </div>

            <div>
                <label class="{{ $label }}" for="ui-date_to">{{ __('courrier.filters.date_to') }}</label>
                <input id="ui-date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="{{ $field }}">
            </div>

            <div class="md:col-span-2 xl:col-span-1">
                <label class="{{ $label }}" for="ui-q">{{ __('courrier.search_global') }}</label>
                <input id="ui-q" type="text" name="q" value="{{ $filters['q'] }}"
                       placeholder="{{ __('courrier.search_global_placeholder') }}"
                       class="{{ $field }}">
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-5">
            <x-buttons.primary type="submit">
                {{ __('courrier.search_submit') }}
            </x-buttons.primary>
            <x-buttons.secondary :href="route('courriers.search')">
                {{ __('courrier.filters.reset') }}
            </x-buttons.secondary>
        </div>
    </form>

    {{-- Résultats --}}
    @if($courriers !== null)
        <div class="overflow-hidden ui-card shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
                <h2 class="m-0 text-sm font-bold text-slate-900">{{ __('courrier.search_results') }}</h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                    {{ $courriers->total() }} {{ __('courrier.search_hits') }}
                </span>
            </div>

            <div class="overflow-x-auto ui-table-scroll">
                <table class="ui-table min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-bold">{{ __('courrier.ref') }}</th>
                            <th class="px-5 py-3 font-bold">{{ __('courrier.search_tf') }}</th>
                            <th class="px-5 py-3 font-bold">{{ __('courrier.subject') }}</th>
                            <th class="px-5 py-3 font-bold">{{ __('courrier.filters.type') }}</th>
                            <th class="px-5 py-3 font-bold">{{ __('courrier.show_bureau') }}</th>
                            <th class="px-5 py-3 font-bold">{{ __('courrier.filters.etape') }}</th>
                            <th class="px-5 py-3 font-bold">{{ __('courrier.filters.date_from') }}</th>
                            <th class="px-5 py-3 text-end font-bold">{{ __('courrier.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($courriers as $c)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-3.5 font-semibold text-slate-800">{{ $c->reference_interne }}</td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $c->tf ?: ($c->reference_externe ?: '—') }}</td>
                                <td class="max-w-[240px] truncate px-5 py-3.5 text-slate-700" title="{{ $c->objet }}">
                                    {{ $c->objet }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase text-slate-600">
                                        {{ $c->type }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">
                                    {{ $c->bureauActuel?->nom ?? '—' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                          style="color: {{ $c->workflowEtape?->ui_color ?? '#64748B' }}; background: {{ ($c->workflowEtape?->ui_color ?? '#64748B') }}18;">
                                        {{ $c->workflowEtape?->libelle ?? '—' }}
                                    </span>
                                    @if($c->is_late)
                                        <span class="ms-1 rounded bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">
                                            {{ __('courrier.kanban_late') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-slate-500">
                                    {{ optional($c->date_reception ?? $c->date_courrier)->format('d/m/Y') ?: '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-end">
                                    <x-buttons.primary :href="route('courriers.show', $c)">
                                        {{ __('courrier.search_open') }}
                                    </x-buttons.primary>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-4">
                                    <x-empty-state
                                        :title="__('courrier.search_empty')"
                                        description="Affinez vos critères ou élargissez la période de recherche."
                                    >
                                        <x-slot:icon>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            </svg>
                                        </x-slot:icon>
                                    </x-empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($courriers->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">
                    {{ $courriers->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm">
            {{ __('courrier.search_hint') }}
        </div>
    @endif
</x-app-layout>
