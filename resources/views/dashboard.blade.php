<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ __('Tableau de bord') }}</h1>
                <p class="mb-0 text-sm text-gray-500">Vue d'ensemble de vos activités et de vos indicateurs</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-600 shadow-sm">
                {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 border-l-4 border-l-[#17384b] bg-white p-5 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ __('app.kpi_incoming') }}</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $kpiIncoming }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 border-l-4 border-l-[#dc633a] bg-white p-5 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ __('app.kpi_in_progress') }}</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $kpiInProgress }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 border-l-4 border-l-emerald-500 bg-white p-5 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ __('app.kpi_archived') }}</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $kpiArchived }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="m-0 text-sm font-bold text-gray-900">Courriers récents nécessitant une attention</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3">Référence</th>
                            <th class="px-4 py-3">Objet</th>
                            <th class="px-4 py-3">Assigné à</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentCourriers as $c)
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $c->reference_interne }}</td>
                                <td class="max-w-xs truncate px-4 py-3 text-gray-700">{{ \Illuminate\Support\Str::limit($c->objet, 45) }}</td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ $c->assignedTo?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-buttons.secondary :href="route('courriers.show', $c)">
                                        Voir
                                    </x-buttons.secondary>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-4">
                                    <x-empty-state title="Aucun courrier en attente d'action." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-gray-900">Raccourcis rapides</h2>
            <div class="flex flex-col gap-2">
                @can('courrier.create')
                    <x-buttons.primary :href="route('courriers.create')" class="w-full">
                        Enregistrer un courrier
                    </x-buttons.primary>
                @endcan

                <x-buttons.secondary :href="route('messages.index')" class="w-full">
                    Ouvrir la messagerie
                </x-buttons.secondary>

                @role('super-admin')
                    <x-buttons.secondary :href="route('admin.users.index')" class="w-full">
                        Gérer les utilisateurs
                    </x-buttons.secondary>
                @endrole
            </div>
        </div>
    </div>
</x-app-layout>
