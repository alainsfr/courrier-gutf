@php
    $field = 'w-full border border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-1';
    $label = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ __('courrier.title') }}</h1>
                <p class="mb-0 text-sm text-gray-500">{{ __('Registre des Courriers') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('courrier.export')
                    <x-buttons.secondary :href="route('courriers.export.excel', request()->query())">
                        {{ __('courrier.export_excel') }}
                    </x-buttons.secondary>
                    <x-buttons.secondary :href="route('courriers.export.pdf', request()->query())">
                        {{ __('courrier.export_pdf') }}
                    </x-buttons.secondary>
                @endcan

                @can('courrier.create')
                    <x-buttons.primary :href="route('courriers.create')">
                        + {{ __('courrier.new') }}
                    </x-buttons.primary>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('courriers.index') }}">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-2">
                    <label class="{{ $label }}">{{ __('courrier.filters.search') }}</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                           class="{{ $field }}"
                           placeholder="{{ __('courrier.filters.search_placeholder') }}">
                </div>

                <div>
                    <label class="{{ $label }}">{{ __('courrier.filters.type') }}</label>
                    <select name="type" class="{{ $field }}">
                        <option value="">{{ __('courrier.filters.all') }}</option>
                        <option value="entrant" @selected(($filters['type'] ?? '') === 'entrant')>{{ __('courrier.type_in') }}</option>
                        <option value="sortant" @selected(($filters['type'] ?? '') === 'sortant')>{{ __('courrier.type_out') }}</option>
                        <option value="interne" @selected(($filters['type'] ?? '') === 'interne')>{{ __('courrier.type_internal') }}</option>
                    </select>
                </div>

                <div>
                    <label class="{{ $label }}">{{ __('courrier.filters.etape') }}</label>
                    <select name="workflow_etape_id" class="{{ $field }}">
                        <option value="">{{ __('courrier.filters.all') }}</option>
                        @foreach($etapes as $etape)
                            <option value="{{ $etape->id }}" @selected(($filters['workflow_etape_id'] ?? '') == $etape->id)>
                                {{ $etape->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $label }}">{{ __('courrier.filters.attachments') }}</label>
                    <select name="has_attachments" class="{{ $field }}">
                        <option value="">{{ __('courrier.filters.all') }}</option>
                        <option value="yes" @selected(($filters['has_attachments'] ?? '') === 'yes')>{{ __('courrier.filters.attachments_yes') }}</option>
                        <option value="no" @selected(($filters['has_attachments'] ?? '') === 'no')>{{ __('courrier.filters.attachments_no') }}</option>
                    </select>
                </div>

                <div>
                    <label class="{{ $label }}">{{ __('courrier.filters.assigned_to') }}</label>
                    <select name="assigned_to" class="{{ $field }}">
                        <option value="">{{ __('courrier.filters.all') }}</option>
                        @foreach($agentsList as $u)
                            <option value="{{ $u->id }}" @selected(($filters['assigned_to'] ?? '') == $u->id)>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $label }}">{{ __('courrier.filters.date_from') }}</label>
                    <input type="date" name="date_from" class="{{ $field }}" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div>
                    <label class="{{ $label }}">{{ __('courrier.filters.date_to') }}</label>
                    <input type="date" name="date_to" class="{{ $field }}" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div>
                    <label class="{{ $label }}">{{ __('courrier.filters.scope') }}</label>
                    <select name="scope" class="{{ $field }}" @if(!$isAdmin) disabled @endif>
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
            </div>

            <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-100 pt-5">
                <x-buttons.primary type="submit">
                    {{ __('courrier.filters.apply') }}
                </x-buttons.primary>
                <x-buttons.secondary :href="route('courriers.index')">
                    {{ __('courrier.filters.reset') }}
                </x-buttons.secondary>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3">{{ __('courrier.ref') }}</th>
                        <th class="px-4 py-3">{{ __('courrier.type') }}</th>
                        <th class="px-4 py-3">{{ __('courrier.subject') }}</th>
                        <th class="px-4 py-3">{{ __('courrier.assigned_to') }}</th>
                        <th class="px-4 py-3">{{ __('courrier.filters.etape') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('courrier.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($courriers as $c)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 font-semibold text-gray-900">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span>{{ $c->reference_interne }}</span>
                                    @if(($c->attachments_count ?? 0) > 0)
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500">
                                            {{ $c->attachments_count }} PJ
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium uppercase text-slate-700">
                                    {{ $c->type }}
                                </span>
                            </td>
                            <td class="max-w-xs truncate px-4 py-3 font-medium text-gray-800" title="{{ $c->objet }}">
                                {{ \Illuminate\Support\Str::limit($c->objet, 60) }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($c->assignedTo)
                                    <div class="font-medium text-gray-800">{{ $c->assignedTo->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $c->assignedTo->email }}</div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                      style="color: {{ $c->workflowEtape?->ui_color ?? '#64748B' }}; background: {{ ($c->workflowEtape?->ui_color ?? '#64748B') }}18;">
                                    {{ $c->workflowEtape?->libelle ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-buttons.secondary :href="route('courriers.show', $c)">
                                    {{ __('courrier.view') }}
                                </x-buttons.secondary>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4">
                                <x-empty-state :title="__('courrier.empty')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $courriers->links() }}
    </div>
</x-app-layout>
