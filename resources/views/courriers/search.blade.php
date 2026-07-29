<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="mb-1 text-lg font-bold text-gray-900">{{ __('courrier.search_title') }}</h1>
                <p class="mb-0 text-sm text-gray-500">{{ __('courrier.search_subtitle') }}</p>
            </div>
            <a href="{{ route('courriers.kanban') }}"
               class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-600 no-underline hover:bg-gray-50">
                {{ __('nav.kanban') }}
            </a>
        </div>
    </x-slot>

    {{-- Formulaire multicritère --}}
    <form method="GET" action="{{ route('courriers.search') }}"
          class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <input type="hidden" name="search" value="1">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    {{ __('courrier.search_tf') }}
                </label>
                <input type="text" name="tf" value="{{ $filters['tf'] }}"
                       placeholder="{{ __('courrier.search_tf_placeholder') }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-800 transition focus:border-[#17384b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/10">
            </div>

            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    {{ __('courrier.filters.type') }}
                </label>
                <select name="type"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-800 transition focus:border-[#17384b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/10">
                    <option value="">{{ __('courrier.filters.all') }}</option>
                    <option value="entrant" @selected($filters['type'] === 'entrant')>{{ __('courrier.type_in') }}</option>
                    <option value="sortant" @selected($filters['type'] === 'sortant')>{{ __('courrier.type_out') }}</option>
                    <option value="interne" @selected($filters['type'] === 'interne')>{{ __('courrier.type_internal') }}</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    {{ __('courrier.show_bureau') }}
                </label>
                <select name="bureau_actuel_id"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-800 transition focus:border-[#17384b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/10">
                    <option value="">{{ __('courrier.filters.all') }}</option>
                    @foreach($bureaux as $bureau)
                        <option value="{{ $bureau->id }}" @selected($filters['bureau_actuel_id'] == $bureau->id)>
                            {{ $bureau->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    {{ __('courrier.filters.date_from') }}
                </label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-800 transition focus:border-[#17384b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/10">
            </div>

            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    {{ __('courrier.filters.date_to') }}
                </label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-800 transition focus:border-[#17384b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/10">
            </div>

            <div class="md:col-span-2 xl:col-span-1">
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    {{ __('courrier.search_global') }}
                </label>
                <input type="text" name="q" value="{{ $filters['q'] }}"
                       placeholder="{{ __('courrier.search_global_placeholder') }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-800 transition focus:border-[#17384b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/10">
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-5">
            <button type="submit"
                    class="rounded-xl bg-[#17384b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#122c3b]">
                {{ __('courrier.search_submit') }}
            </button>
            <a href="{{ route('courriers.search') }}"
               class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 no-underline hover:bg-gray-50">
                {{ __('courrier.filters.reset') }}
            </a>
        </div>
    </form>

    {{-- Résultats --}}
    @if($courriers !== null)
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3.5">
                <h2 class="m-0 text-sm font-bold text-gray-900">{{ __('courrier.search_results') }}</h2>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                    {{ $courriers->total() }} {{ __('courrier.search_hits') }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
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
                    <tbody class="divide-y divide-gray-200">
                        @forelse($courriers as $c)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-5 py-3.5 font-semibold text-gray-800">{{ $c->reference_interne }}</td>
                                <td class="px-5 py-3.5 text-gray-600">{{ $c->tf ?: ($c->reference_externe ?: '—') }}</td>
                                <td class="max-w-[240px] truncate px-5 py-3.5 text-gray-700" title="{{ $c->objet }}">
                                    {{ $c->objet }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-semibold uppercase text-gray-600">
                                        {{ $c->type }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-600">
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
                                <td class="px-5 py-3.5 text-gray-500">
                                    {{ optional($c->date_reception ?? $c->date_courrier)->format('d/m/Y') ?: '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-end">
                                    <a href="{{ route('courriers.show', $c) }}"
                                       class="inline-flex items-center gap-1 rounded-lg bg-[#17384b] px-3 py-1.5 text-xs font-semibold text-white no-underline hover:bg-[#122c3b]">
                                        {{ __('courrier.search_open') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-400">
                                    {{ __('courrier.search_empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($courriers->hasPages())
                <div class="border-t border-gray-200 px-5 py-3">
                    {{ $courriers->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-12 text-center text-sm text-gray-500 shadow-sm">
            {{ __('courrier.search_hint') }}
        </div>
    @endif
</x-app-layout>
