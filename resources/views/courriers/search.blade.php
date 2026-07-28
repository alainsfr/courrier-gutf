<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="mb-1 text-lg font-bold text-slate-900">{{ __('courrier.search_title') }}</h1>
                <p class="mb-0 text-sm text-slate-500">{{ __('courrier.search_subtitle') }}</p>
            </div>
            <a href="{{ route('courriers.kanban') }}"
               class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 no-underline hover:bg-slate-50">
                {{ __('nav.kanban') }}
            </a>
        </div>
    </x-slot>

    {{-- Formulaire multicritère --}}
    <form method="GET" action="{{ route('courriers.search') }}"
          class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
        <input type="hidden" name="search" value="1">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    {{ __('courrier.search_tf') }}
                </label>
                <input type="text" name="tf" value="{{ $filters['tf'] }}"
                       placeholder="{{ __('courrier.search_tf_placeholder') }}"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    {{ __('courrier.filters.type') }}
                </label>
                <select name="type"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option value="">{{ __('courrier.filters.all') }}</option>
                    <option value="entrant" @selected($filters['type'] === 'entrant')>{{ __('courrier.type_in') }}</option>
                    <option value="sortant" @selected($filters['type'] === 'sortant')>{{ __('courrier.type_out') }}</option>
                    <option value="interne" @selected($filters['type'] === 'interne')>{{ __('courrier.type_internal') }}</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    {{ __('courrier.show_bureau') }}
                </label>
                <select name="bureau_actuel_id"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option value="">{{ __('courrier.filters.all') }}</option>
                    @foreach($bureaux as $bureau)
                        <option value="{{ $bureau->id }}" @selected($filters['bureau_actuel_id'] == $bureau->id)>
                            {{ $bureau->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    {{ __('courrier.filters.date_from') }}
                </label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    {{ __('courrier.filters.date_to') }}
                </label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>

            <div class="md:col-span-2 xl:col-span-1">
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    {{ __('courrier.search_global') }}
                </label>
                <input type="text" name="q" value="{{ $filters['q'] }}"
                       placeholder="{{ __('courrier.search_global_placeholder') }}"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="submit"
                    class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-800">
                {{ __('courrier.search_submit') }}
            </button>
            <a href="{{ route('courriers.search') }}"
               class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 no-underline hover:bg-slate-50">
                {{ __('courrier.filters.reset') }}
            </a>
        </div>
    </form>

    {{-- Résultats --}}
    @if($courriers !== null)
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h2 class="m-0 text-sm font-bold text-slate-900">{{ __('courrier.search_results') }}</h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                    {{ $courriers->total() }} {{ __('courrier.search_hits') }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-bold">{{ __('courrier.ref') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('courrier.search_tf') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('courrier.subject') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('courrier.filters.type') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('courrier.show_bureau') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('courrier.status') }}</th>
                            <th class="px-4 py-3 font-bold">{{ __('courrier.filters.date_from') }}</th>
                            <th class="px-4 py-3 text-end font-bold">{{ __('courrier.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($courriers as $c)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $c->reference_interne }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $c->reference_externe ?: '—' }}</td>
                                <td class="max-w-[240px] truncate px-4 py-3 text-slate-700" title="{{ $c->objet }}">
                                    {{ $c->objet }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase text-slate-600">
                                        {{ $c->type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $c->bureauActuel?->nom ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                          style="color: {{ $c->workflowEtape?->ui_color ?? '#64748B' }}; background: {{ ($c->workflowEtape?->ui_color ?? '#64748B') }}18;">
                                        {{ __('courrier.status_label.'.$c->status) }}
                                    </span>
                                    @if($c->is_late)
                                        <span class="ms-1 rounded bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">
                                            {{ __('courrier.kanban_late') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500">
                                    {{ optional($c->date_reception ?? $c->date_courrier)->format('d/m/Y') ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('courriers.show', $c) }}"
                                       class="inline-flex items-center gap-1 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white no-underline hover:bg-slate-800">
                                        {{ __('courrier.search_open') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-400">
                                    {{ __('courrier.search_empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($courriers->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">
                    {{ $courriers->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
            {{ __('courrier.search_hint') }}
        </div>
    @endif
</x-app-layout>
