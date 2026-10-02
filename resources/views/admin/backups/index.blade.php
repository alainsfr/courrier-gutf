<x-app-layout>
    <x-slot name="header">
        <x-admin-page-header :title="__('backups.title')" :subtitle="__('backups.subtitle')">
            <x-slot:actions>
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 shadow-sm">
                    {{ __('backups.schedule_hint') }}
                </div>
            </x-slot:actions>
        </x-admin-page-header>
    </x-slot>

    {{-- Plan de Reprise d'Activité --}}
    <details class="mb-6 overflow-hidden rounded-xl border border-blue-200 bg-blue-50 open:shadow-sm" open>
        <summary class="cursor-pointer list-none px-5 py-4 text-sm font-bold text-blue-900 marker:content-none [&::-webkit-details-marker]:hidden">
            <span class="inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5 shrink-0 text-blue-700">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                {{ __('backups.pra_title') }}
            </span>
        </summary>
        <div class="space-y-4 border-t border-blue-200/70 px-5 py-4 text-sm text-blue-950">
            <p class="mb-0 text-blue-900/80">{{ __('backups.pra_intro') }}</p>

            <div>
                <h3 class="mb-1 text-sm font-bold">{{ __('backups.pra_a_title') }}</h3>
                <p class="mb-0 text-blue-900/80">{{ __('backups.pra_a_text') }}</p>
            </div>

            <div>
                <h3 class="mb-1 text-sm font-bold">{{ __('backups.pra_b_title') }}</h3>
                <p class="mb-2 text-blue-900/80">{{ __('backups.pra_b_text') }}</p>
                <pre class="mb-0 overflow-x-auto rounded-lg bg-white/80 px-3 py-2 font-mono text-xs text-slate-800">{{ __('backups.pra_b_cmd') }}</pre>
            </div>

            <div>
                <h3 class="mb-1 text-sm font-bold">{{ __('backups.pra_c_title') }}</h3>
                <p class="mb-0 text-blue-900/80">{{ __('backups.pra_c_text') }}</p>
            </div>

            <div>
                <h3 class="mb-1 text-sm font-bold">{{ __('backups.pra_d_title') }}</h3>
                <p class="mb-0 text-blue-900/80">{{ __('backups.pra_d_text') }}</p>
            </div>

            <div>
                <h3 class="mb-1 text-sm font-bold">{{ __('backups.pra_e_title') }}</h3>
                <p class="mb-2 text-blue-900/80">{{ __('backups.pra_e_text') }}</p>
                <pre class="mb-0 overflow-x-auto rounded-lg bg-white/80 px-3 py-2 font-mono text-xs text-slate-800">{{ __('backups.pra_e_cmd') }}</pre>
            </div>
        </div>
    </details>

    {{-- Tableau des archives --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="mb-0 text-sm font-bold text-gray-900">{{ __('backups.table_title') }}</h2>
        </div>

        @if ($backups->isEmpty())
            <div class="px-5 py-10">
                <x-empty-state
                    :title="__('backups.empty')"
                    :description="__('backups.empty_hint')"
                />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">
                            <th class="px-4 py-3">{{ __('backups.col_name') }}</th>
                            <th class="px-4 py-3">{{ __('backups.col_size') }}</th>
                            <th class="px-4 py-3">{{ __('backups.col_date') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('backups.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($backups as $backup)
                            <tr class="transition hover:bg-gray-50/80">
                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    {{ $backup['name'] }}
                                </td>
                                <td class="px-4 py-3 tabular-nums text-gray-600">
                                    {{ $backup['size_human'] }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $backup['date']?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-buttons.primary
                                        :href="route('admin.backups.download', ['fileName' => $backup['name']])"
                                        class="!px-3 !py-1.5 text-xs"
                                    >
                                        {{ __('backups.download') }}
                                    </x-buttons.primary>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-app-layout>
