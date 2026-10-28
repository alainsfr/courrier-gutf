@php
    $field = 'ui-input';
    $label = 'ui-label';

    $chipBase = 'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold no-underline transition';
    $chipIdle = 'border-slate-200 bg-white text-slate-600 hover:border-brand-soft hover:text-brand';
    $chipActive = 'border-brand bg-brand text-white';

    $qs = fn (array $overrides = []) => array_filter(
        array_merge($filters, $overrides),
        fn ($v) => $v !== null && $v !== ''
    );

    $validationEtapeId = optional($etapes->firstWhere('ordre', 4))->id;
    $filtersOpen = $activeFilterCount > 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <p class="mb-0.5 text-xs font-semibold uppercase tracking-[0.14em] text-accent">
                    {{ __('courrier.register_eyebrow') }}
                </p>
                <h1 class="truncate text-lg font-bold text-slate-900">{{ __('courrier.register_title') }}</h1>
                <p class="mb-0 text-sm text-slate-500">
                    {{ trans_choice('courrier.register_results', $courriers->total(), ['count' => number_format($courriers->total())]) }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-buttons.secondary :href="route('courriers.kanban')">
                    {{ __('courrier.kanban_title') }}
                </x-buttons.secondary>
                <x-buttons.secondary :href="route('courriers.search')">
                    {{ __('courrier.search_title') }}
                </x-buttons.secondary>
                @can('courrier.export')
                    <x-buttons.secondary :href="route('courriers.export.excel', request()->query())">
                        {{ __('courrier.export_excel') }}
                    </x-buttons.secondary>
                    <x-buttons.secondary :href="route('courriers.export.pdf', request()->query())">
                        {{ __('courrier.export_pdf') }}
                    </x-buttons.secondary>
                @endcan
                @can('courrier.create')
                    <x-buttons.primary :href="route('courriers.create')" class="order-first sm:order-none">
                        + {{ __('courrier.new') }}
                    </x-buttons.primary>
                @endcan
            </div>
        </div>
    </x-slot>

    {{-- Raccourcis --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('courriers.index', $qs(['is_late' => '', 'type' => '', 'workflow_etape_id' => '', 'assigned_to' => '', 'archived' => 'active'])) }}"
           class="{{ $chipBase }} {{ ($filters['is_late'] === '' && $filters['type'] === '' && $filters['workflow_etape_id'] === '' && $filters['assigned_to'] === '' && $filters['archived'] === 'active') ? $chipActive : $chipIdle }}">
            {{ __('courrier.register_chip_active') }}
            <span class="tabular-nums opacity-80">{{ number_format($chips['active']) }}</span>
        </a>
        <a href="{{ route('courriers.index', $qs(['is_late' => '1', 'type' => '', 'workflow_etape_id' => '', 'assigned_to' => '', 'archived' => 'active'])) }}"
           class="{{ $chipBase }} {{ $filters['is_late'] === '1' ? 'border-rose-600 bg-rose-600 text-white' : 'border-rose-200 bg-rose-50 text-rose-700 hover:border-rose-300' }}">
            {{ __('courrier.register_chip_late') }}
            <span class="tabular-nums opacity-80">{{ number_format($chips['late']) }}</span>
        </a>
        <a href="{{ route('courriers.index', $qs(['type' => 'entrant', 'is_late' => '', 'workflow_etape_id' => '', 'assigned_to' => '', 'archived' => 'active'])) }}"
           class="{{ $chipBase }} {{ $filters['type'] === 'entrant' ? $chipActive : $chipIdle }}">
            {{ __('courrier.type_in') }}
            <span class="tabular-nums opacity-80">{{ number_format($chips['entrant']) }}</span>
        </a>
        <a href="{{ route('courriers.index', $qs(['type' => 'sortant', 'is_late' => '', 'workflow_etape_id' => '', 'assigned_to' => '', 'archived' => 'active'])) }}"
           class="{{ $chipBase }} {{ $filters['type'] === 'sortant' ? $chipActive : $chipIdle }}">
            {{ __('courrier.type_out') }}
            <span class="tabular-nums opacity-80">{{ number_format($chips['sortant']) }}</span>
        </a>
        @if ($validationEtapeId && $chips['validation'] > 0 && auth()->user()?->can('courrier.validate'))
            <a href="{{ route('courriers.index', $qs(['workflow_etape_id' => $validationEtapeId, 'is_late' => '', 'type' => '', 'assigned_to' => '', 'archived' => 'active'])) }}"
               class="{{ $chipBase }} {{ (string) $filters['workflow_etape_id'] === (string) $validationEtapeId ? $chipActive : $chipIdle }}">
                {{ __('courrier.register_chip_validation') }}
                <span class="tabular-nums opacity-80">{{ number_format($chips['validation']) }}</span>
            </a>
        @endif
        <a href="{{ route('courriers.index', $qs(['assigned_to' => 'unassigned', 'is_late' => '', 'type' => '', 'workflow_etape_id' => '', 'archived' => 'active'])) }}"
           class="{{ $chipBase }} {{ $filters['assigned_to'] === 'unassigned' ? $chipActive : $chipIdle }}">
            {{ __('courrier.register_chip_unassigned') }}
            <span class="tabular-nums opacity-80">{{ number_format($chips['unassigned']) }}</span>
        </a>
    </div>

    {{-- Filtres --}}
    <div class="mb-5 overflow-hidden ui-card shadow-sm" data-register-filters>
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
            <div class="flex items-center gap-2">
                <h2 class="mb-0 text-sm font-bold text-slate-900">{{ __('courrier.register_filters') }}</h2>
                @if ($activeFilterCount > 0)
                    <span class="inline-flex items-center rounded-full bg-accent-soft px-2 py-0.5 text-[11px] font-bold text-accent">
                        {{ $activeFilterCount }}
                    </span>
                @endif
            </div>
            <button type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand hover:bg-slate-50"
                    data-filters-toggle
                    aria-expanded="{{ $filtersOpen ? 'true' : 'false' }}">
                <span data-filters-label>{{ $filtersOpen ? __('courrier.register_filters_hide') : __('courrier.register_filters_show') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                     class="h-4 w-4 transition {{ $filtersOpen ? 'rotate-180' : '' }}" data-filters-chevron>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        </div>

        <form method="GET" action="{{ route('courriers.index') }}" class="{{ $filtersOpen ? '' : 'hidden' }}" data-filters-panel>
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
            <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-2">
                    <label for="register-q-1" class="{{ $label }}">{{ __('courrier.filters.search') }}</label>
                    <input id="register-q-1" type="search" name="q" value="{{ $filters['q'] }}" class="{{ $field }}"
                           placeholder="{{ __('courrier.filters.search_placeholder') }}">
                </div>

                <div>
                    <label for="register-type-1" class="{{ $label }}">{{ __('courrier.filters.type') }}</label>
                    <select id="register-type-1" name="type" class="{{ $field }}">
                        <option value="">{{ __('courrier.filters.all') }}</option>
                        <option value="entrant" @selected($filters['type'] === 'entrant')>{{ __('courrier.type_in') }}</option>
                        <option value="sortant" @selected($filters['type'] === 'sortant')>{{ __('courrier.type_out') }}</option>
                        <option value="interne" @selected($filters['type'] === 'interne')>{{ __('courrier.type_internal') }}</option>
                    </select>
                </div>

                <div>
                    <label for="register-workflow_etape_id-1" class="{{ $label }}">{{ __('courrier.filters.etape') }}</label>
                    <select id="register-workflow_etape_id-1" name="workflow_etape_id" class="{{ $field }}">
                        <option value="">{{ __('courrier.filters.all') }}</option>
                        @foreach ($etapes as $etape)
                            <option value="{{ $etape->id }}" @selected((string) $filters['workflow_etape_id'] === (string) $etape->id)>
                                {{ $etape->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="register-bureau_actuel_id-1" class="{{ $label }}">{{ __('courrier.filters.bureau') }}</label>
                    <select id="register-bureau_actuel_id-1" name="bureau_actuel_id" class="{{ $field }}">
                        <option value="">{{ __('courrier.filters.all') }}</option>
                        @foreach ($bureaux as $bureau)
                            <option value="{{ $bureau->id }}" @selected((string) $filters['bureau_actuel_id'] === (string) $bureau->id)>
                                {{ $bureau->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="register-assigned_to-1" class="{{ $label }}">{{ __('courrier.filters.assigned_to') }}</label>
                    <select id="register-assigned_to-1" name="assigned_to" class="{{ $field }}">
                        <option value="">{{ __('courrier.filters.all') }}</option>
                        <option value="unassigned" @selected($filters['assigned_to'] === 'unassigned')>{{ __('courrier.register_chip_unassigned') }}</option>
                        @foreach ($agentsList as $u)
                            <option value="{{ $u->id }}" @selected((string) $filters['assigned_to'] === (string) $u->id)>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="register-has_attachments-1" class="{{ $label }}">{{ __('courrier.filters.attachments') }}</label>
                    <select id="register-has_attachments-1" name="has_attachments" class="{{ $field }}">
                        <option value="">{{ __('courrier.filters.all') }}</option>
                        <option value="yes" @selected($filters['has_attachments'] === 'yes')>{{ __('courrier.filters.attachments_yes') }}</option>
                        <option value="no" @selected($filters['has_attachments'] === 'no')>{{ __('courrier.filters.attachments_no') }}</option>
                    </select>
                </div>

                <div>
                    <label for="register-is_late-1" class="{{ $label }}">{{ __('courrier.filters.sla') }}</label>
                    <select id="register-is_late-1" name="is_late" class="{{ $field }}">
                        <option value="">{{ __('courrier.filters.all') }}</option>
                        <option value="1" @selected($filters['is_late'] === '1')>{{ __('courrier.register_chip_late') }}</option>
                    </select>
                </div>

                <div>
                    <label for="register-archived-1" class="{{ $label }}">{{ __('courrier.filters.archived') }}</label>
                    <select id="register-archived-1" name="archived" class="{{ $field }}">
                        <option value="active" @selected($filters['archived'] === 'active')>{{ __('courrier.filters.archived_active') }}</option>
                        <option value="archived" @selected($filters['archived'] === 'archived')>{{ __('courrier.filters.archived_only') }}</option>
                        <option value="all" @selected($filters['archived'] === 'all')>{{ __('courrier.filters.archived_all') }}</option>
                    </select>
                </div>

                <div>
                    <label for="register-date_from-1" class="{{ $label }}">{{ __('courrier.filters.date_from') }}</label>
                    <input id="register-date_from-1" type="date" name="date_from" class="{{ $field }}" value="{{ $filters['date_from'] }}">
                </div>

                <div>
                    <label for="register-date_to-1" class="{{ $label }}">{{ __('courrier.filters.date_to') }}</label>
                    <input id="register-date_to-1" type="date" name="date_to" class="{{ $field }}" value="{{ $filters['date_to'] }}">
                </div>

                @if ($isAdmin)
                    <div>
                        <label for="register-scope-1" class="{{ $label }}">{{ __('courrier.filters.scope') }}</label>
                        <select id="register-scope-1" name="scope" class="{{ $field }}">
                            <option value="all" @selected($filters['scope'] === 'all')>{{ __('courrier.filters.scope_all') }}</option>
                            <option value="mine" @selected($filters['scope'] === 'mine')>{{ __('courrier.filters.scope_mine') }}</option>
                        </select>
                    </div>
                @else
                    <input type="hidden" name="scope" value="mine">
                @endif
            </div>

            <div class="flex flex-wrap gap-2 border-t border-slate-100 px-5 py-4">
                <x-buttons.primary type="submit">{{ __('courrier.filters.apply') }}</x-buttons.primary>
                <x-buttons.secondary :href="route('courriers.index')">{{ __('courrier.filters.reset') }}</x-buttons.secondary>
            </div>
        </form>

        {{-- Barre recherche rapide toujours visible quand panneau fermé --}}
        @if (! $filtersOpen)
            <form method="GET" action="{{ route('courriers.index') }}" class="flex flex-wrap items-end gap-3 p-5" data-filters-quick>
                @foreach (collect($filters)->except(['q']) as $key => $value)
                    @if ($value !== null && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <div class="min-w-[16rem] flex-1">
                    <label for="register-q-2" class="{{ $label }}">{{ __('courrier.filters.search') }}</label>
                    <input id="register-q-2" type="search" name="q" value="{{ $filters['q'] }}" class="{{ $field }}"
                           placeholder="{{ __('courrier.filters.search_placeholder') }}">
                </div>
                <x-buttons.primary type="submit">{{ __('courrier.filters.apply') }}</x-buttons.primary>
            </form>
        @endif
    </div>

    {{-- Toolbar résultats --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <p class="mb-0 text-sm text-slate-500">
            @if ($courriers->total() > 0)
                {{ __('courrier.register_showing', [
                    'from' => $courriers->firstItem(),
                    'to' => $courriers->lastItem(),
                    'total' => number_format($courriers->total()),
                ]) }}
            @else
                {{ __('courrier.empty') }}
            @endif
        </p>

        <form method="GET" action="{{ route('courriers.index') }}" class="flex flex-wrap items-center gap-2" data-toolbar-form>
            @foreach (collect($filters)->except(['sort', 'per_page']) as $key => $value)
                @if ($value !== null && $value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label for="register-sort-1" class="text-xs font-semibold text-slate-500">{{ __('courrier.register_sort') }}</label>
            <select id="register-sort-1" name="sort" class="ui-input rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm" onchange="this.form.submit()">
                <option value="recent" @selected($filters['sort'] === 'recent')>{{ __('courrier.register_sort_recent') }}</option>
                <option value="oldest" @selected($filters['sort'] === 'oldest')>{{ __('courrier.register_sort_oldest') }}</option>
                <option value="deadline" @selected($filters['sort'] === 'deadline')>{{ __('courrier.register_sort_deadline') }}</option>
                <option value="updated" @selected($filters['sort'] === 'updated')>{{ __('courrier.register_sort_updated') }}</option>
                <option value="ref" @selected($filters['sort'] === 'ref')>{{ __('courrier.register_sort_ref') }}</option>
            </select>
            <select name="per_page" aria-label="{{ __('ui.per_page') }}" class="ui-input rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm" onchange="this.form.submit()">
                <option value="10" @selected((int) $filters['per_page'] === 10)>10</option>
                <option value="20" @selected((int) $filters['per_page'] === 20)>20</option>
                <option value="50" @selected((int) $filters['per_page'] === 50)>50</option>
            </select>
        </form>
    </div>

    {{-- Desktop table --}}
    <div class="hidden overflow-hidden ui-card shadow-sm md:block">
        <div class="overflow-x-auto ui-table-scroll">
            <table class="ui-table courrier-register-table min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-4 py-3">{{ __('courrier.ref') }}</th>
                        <th class="px-4 py-3">{{ __('courrier.type') }}</th>
                        <th class="px-4 py-3">{{ __('courrier.subject') }}</th>
                        <th class="px-4 py-3">{{ __('courrier.show_bureau') }}</th>
                        <th class="px-4 py-3">{{ __('courrier.filters.etape') }}</th>
                        <th class="px-4 py-3">{{ __('courrier.assigned_to') }}</th>
                        <th class="px-4 py-3">{{ __('courrier.export_date') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('courrier.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($courriers as $c)
                        @php
                            $date = $c->date_reception?->format('d/m/Y')
                                ?? $c->date_courrier?->format('d/m/Y')
                                ?? $c->created_at?->format('d/m/Y');
                            $typeLabel = match ($c->type) {
                                'entrant' => __('courrier.type_in'),
                                'sortant' => __('courrier.type_out'),
                                'interne' => __('courrier.type_internal'),
                                default => strtoupper((string) $c->type),
                            };
                        @endphp
                        <tr class="cursor-pointer transition hover:bg-slate-50/80"
                            onclick="window.location='{{ route('courriers.show', $c) }}'">
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="font-semibold text-slate-900">{{ $c->reference_interne }}</span>
                                    @if ($c->is_late)
                                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-700">
                                            {{ __('courrier.kanban_late') }}
                                        </span>
                                    @endif
                                    @if (($c->attachments_count ?? 0) > 0)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                                            {{ $c->attachments_count }} PJ
                                        </span>
                                    @endif
                                </div>
                                @if ($c->tf || $c->reference_externe)
                                    <div class="mt-0.5 text-[11px] text-slate-400">
                                        {{ $c->tf ?: $c->reference_externe }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase text-slate-700">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="max-w-xs px-4 py-3">
                                <div class="truncate font-medium text-slate-800" title="{{ $c->objet }}">
                                    {{ \Illuminate\Support\Str::limit($c->objet, 55) }}
                                </div>
                                @if ($c->expediteur || $c->destinataire || $c->proprietaire)
                                    <div class="mt-0.5 truncate text-xs text-slate-400">
                                        {{ $c->proprietaire ?: ($c->expediteur ?: $c->destinataire) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($c->bureauActuel)
                                    <span class="inline-flex items-center gap-1.5 text-slate-700">
                                        <span class="inline-block h-2 w-2 rounded-full" style="background: {{ $c->bureauActuel->color ?: '#94a3b8' }}"></span>
                                        {{ $c->bureauActuel->nom }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                      style="color: {{ $c->workflowEtape?->ui_color ?? '#64748B' }}; background: {{ ($c->workflowEtape?->ui_color ?? '#64748B') }}18;">
                                    {{ $c->workflowEtape?->libelle ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $c->assignedTo?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 tabular-nums text-slate-500">
                                {{ $date ?? '—' }}
                                @if ($c->date_limite)
                                    <div class="text-[11px] {{ $c->is_late ? 'font-semibold text-rose-600' : 'text-slate-400' }}">
                                        {{ __('courrier.register_deadline') }} {{ $c->date_limite->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
                                <x-buttons.secondary :href="route('courriers.show', $c)" class="!px-2.5 !py-1.5 text-xs">
                                    {{ __('courrier.view') }}
                                </x-buttons.secondary>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10">
                                <x-empty-state
                                    :title="__('courrier.empty')"
                                    :description="__('courrier.register_empty_hint')"
                                >
                                    @can('courrier.create')
                                        <x-buttons.primary :href="route('courriers.create')" class="mt-4">
                                            + {{ __('courrier.new') }}
                                        </x-buttons.primary>
                                    @endcan
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        @forelse ($courriers as $c)
            @php
                $date = $c->date_reception?->format('d/m/Y')
                    ?? $c->date_courrier?->format('d/m/Y')
                    ?? $c->created_at?->format('d/m/Y');
                $typeLabel = match ($c->type) {
                    'entrant' => __('courrier.type_in'),
                    'sortant' => __('courrier.type_out'),
                    'interne' => __('courrier.type_internal'),
                    default => strtoupper((string) $c->type),
                };
            @endphp
            <a href="{{ route('courriers.show', $c) }}"
               class="block rounded-xl border {{ $c->is_late ? 'border-rose-200' : 'border-slate-200' }} bg-white p-4 shadow-sm no-underline transition active:scale-[0.99]">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="whitespace-nowrap font-bold text-slate-900">{{ $c->reference_interne }}</span>
                            @if ($c->is_late)
                                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700">
                                    {{ __('courrier.kanban_late') }}
                                </span>
                            @endif
                        </div>
                        <p class="mb-0 mt-1 text-sm font-medium text-slate-800">{{ \Illuminate\Support\Str::limit($c->objet, 70) }}</p>
                    </div>
                    <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600">
                        {{ $typeLabel }}
                    </span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                    <span class="inline-flex rounded-md px-2 py-0.5 font-semibold"
                          style="color: {{ $c->workflowEtape?->ui_color ?? '#64748B' }}; background: {{ ($c->workflowEtape?->ui_color ?? '#64748B') }}18;">
                        {{ $c->workflowEtape?->libelle ?? '—' }}
                    </span>
                    @if ($c->bureauActuel)
                        <span>{{ $c->bureauActuel->nom }}</span>
                    @endif
                    <span>{{ $date ?? '—' }}</span>
                    @if (($c->attachments_count ?? 0) > 0)
                        <span>{{ $c->attachments_count }} PJ</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="ui-card p-6 shadow-sm">
                <x-empty-state
                    :title="__('courrier.empty')"
                    :description="__('courrier.register_empty_hint')"
                />
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $courriers->links() }}
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const root = document.querySelector('[data-register-filters]');
                if (!root) return;
                const toggle = root.querySelector('[data-filters-toggle]');
                const panel = root.querySelector('[data-filters-panel]');
                const quick = root.querySelector('[data-filters-quick]');
                const label = root.querySelector('[data-filters-label]');
                const chevron = root.querySelector('[data-filters-chevron]');
                const showLabel = @json(__('courrier.register_filters_show'));
                const hideLabel = @json(__('courrier.register_filters_hide'));

                toggle?.addEventListener('click', () => {
                    const open = panel?.classList.contains('hidden');
                    panel?.classList.toggle('hidden', !open);
                    quick?.classList.toggle('hidden', open);
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (label) label.textContent = open ? hideLabel : showLabel;
                    chevron?.classList.toggle('rotate-180', open);
                });
            });
        </script>
    @endpush
</x-app-layout>
