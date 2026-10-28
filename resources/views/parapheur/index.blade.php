<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <p class="mb-0.5 text-xs font-semibold uppercase tracking-[0.14em] text-accent">
                    {{ __('parapheur.nav') }}
                </p>
                <h1 class="truncate text-lg font-bold text-slate-900">{{ __('parapheur.title') }}</h1>
                <p class="mb-0 text-sm text-slate-500">{{ __('parapheur.subtitle') }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 shadow-sm">
                {{ trans_choice('parapheur.results', $visas->total(), ['count' => number_format($visas->total())]) }}
            </div>
        </div>
    </x-slot>

    <section class="overflow-hidden ui-card shadow-sm">
        @if ($visas->isEmpty())
            <div class="px-5 py-10">
                <x-empty-state
                    :title="__('parapheur.empty')"
                    :description="__('parapheur.empty_hint')"
                />
            </div>
        @else
            <div class="overflow-x-auto ui-table-scroll">
                <table class="ui-table min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="px-4 py-3">{{ __('parapheur.col_ref') }}</th>
                            <th class="px-4 py-3">{{ __('parapheur.col_subject') }}</th>
                            <th class="px-4 py-3">{{ __('parapheur.col_type') }}</th>
                            <th class="px-4 py-3">{{ __('parapheur.col_bureau') }}</th>
                            <th class="px-4 py-3">{{ __('parapheur.col_etape') }}</th>
                            <th class="px-4 py-3">{{ __('parapheur.col_received') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('parapheur.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($visas as $visa)
                            @php $c = $visa->courrier; @endphp
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-3 font-semibold text-slate-900">
                                    {{ $c?->reference_interne ?? '—' }}
                                </td>
                                <td class="max-w-xs truncate px-4 py-3 text-slate-700">
                                    {{ \Illuminate\Support\Str::limit($c?->objet ?? '—', 52) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase text-slate-600">
                                        {{ $c?->type ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $c?->bureauActuel?->nom ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($c?->workflowEtape)
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                              style="color: {{ $c->workflowEtape->ui_color ?? '#64748B' }}; background: {{ ($c->workflowEtape->ui_color ?? '#64748B') }}18;">
                                            {{ $c->workflowEtape->libelle }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500" title="{{ $visa->created_at?->format('d/m/Y H:i') }}">
                                    {{ $visa->created_at?->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($c)
                                        <x-buttons.primary :href="route('courriers.show', $c)" class="!px-3 !py-1.5 text-xs">
                                            {{ __('parapheur.examine') }}
                                        </x-buttons.primary>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($visas->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">
                    {{ $visas->links() }}
                </div>
            @endif
        @endif
    </section>
</x-app-layout>
