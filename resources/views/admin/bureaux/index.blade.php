@php
    $input = 'ui-input';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin-page-header :title="__('bureaux.title')" :subtitle="__('bureaux.subtitle')">
            <x-slot:actions>
                <x-buttons.primary :href="route('admin.bureaux.create')">
                    <span>+</span> {{ __('bureaux.new') }}
                </x-buttons.primary>
            </x-slot:actions>
        </x-admin-page-header>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 ui-alert ui-alert-success">
            {{ session('status') }}
        </div>
    @endif

    <x-form-errors />

    <div class="overflow-hidden ui-card shadow-sm">
        <div class="overflow-x-auto ui-table-scroll">
            <table class="ui-table min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-4 py-3">{{ __('bureaux.nom') }}</th>
                        <th class="px-4 py-3">{{ __('bureaux.code') }}</th>
                        <th class="px-4 py-3">{{ __('bureaux.description') }}</th>
                        <th class="px-4 py-3">{{ __('bureaux.users_count') }}</th>
                        <th class="px-4 py-3">{{ __('bureaux.status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('bureaux.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($bureaux as $b)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $b->nom }}</td>
                            <td class="px-4 py-3">
                                <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-700">{{ $b->code }}</code>
                            </td>
                            <td class="max-w-xs truncate px-4 py-3 text-slate-600">
                                {{ $b->description ? \Illuminate\Support\Str::limit($b->description, 80) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $b->users_count }}</td>
                            <td class="px-4 py-3">
                                @if($b->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ __('bureaux.active') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">{{ __('bureaux.inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.bureaux.edit', $b) }}"
                                   class="text-sm font-semibold text-brand no-underline hover:underline">
                                    {{ __('bureaux.edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4">
                                <x-empty-state
                                    :title="__('bureaux.empty')"
                                    description="Ajoutez un bureau pour structurer le workflow métier."
                                >
                                    <x-slot:icon>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                        </svg>
                                    </x-slot:icon>
                                    <x-buttons.primary :href="route('admin.bureaux.create')">
                                        {{ __('bureaux.new') }}
                                    </x-buttons.primary>
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $bureaux->links() }}</div>
</x-app-layout>
