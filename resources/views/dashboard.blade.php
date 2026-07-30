<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <p class="mb-0.5 text-xs font-semibold uppercase tracking-[0.14em] text-[#dc633a]">
                    {{ __( $greetingKey ) }}
                </p>
                <h1 class="truncate text-lg font-bold text-gray-900">
                    {{ __('app.welcome', ['name' => $user->name]) }}
                </h1>
                <p class="mb-0 text-sm text-gray-500">{{ __('app.dashboard_subtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-600 shadow-sm">
                    {{ now()->translatedFormat('l j F Y') }}
                </div>
                @can('courrier.create')
                    <x-buttons.primary :href="route('courriers.create')">
                        {{ __('app.shortcut_register') }}
                    </x-buttons.primary>
                @endcan
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($kpiLate > 0)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-rose-200 bg-gradient-to-r from-rose-50 to-white px-4 py-3.5 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
                <div>
                    <p class="mb-0 text-sm font-semibold text-rose-800">
                        {{ trans_choice('app.attention_banner', $kpiLate, ['count' => $kpiLate]) }}
                    </p>
                    <p class="mb-0 text-xs text-rose-600/80">{{ __('app.late_title') }}</p>
                </div>
            </div>
            <a href="#late-panel"
               class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white no-underline transition hover:bg-rose-700">
                {{ __('app.attention_cta') }}
            </a>
        </div>
    @endif

    {{-- KPI --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        @php
            $hasReception = $byEtape->contains(fn ($e) => (int) $e->ordre === 1) || $isAdmin;
            $hasValidation = $byEtape->contains(fn ($e) => (int) $e->ordre === 4)
                || $isAdmin
                || auth()->user()?->can('courrier.validate');

            $kpis = collect([
                [
                    'label' => __('app.kpi_active'),
                    'value' => $kpiActive,
                    'tone' => 'brand',
                    'href' => route('courriers.kanban'),
                    'show' => true,
                ],
                [
                    'label' => __('app.kpi_late'),
                    'value' => $kpiLate,
                    'tone' => $kpiLate > 0 ? 'danger' : 'muted',
                    'href' => '#late-panel',
                    'show' => true,
                ],
                [
                    'label' => __('app.kpi_validation'),
                    'value' => $kpiValidation,
                    'tone' => 'accent',
                    'href' => route('courriers.index', ['workflow_etape_id' => $byEtape->firstWhere('ordre', 4)?->id]),
                    'show' => $hasValidation,
                ],
                [
                    'label' => __('app.kpi_reception'),
                    'value' => $kpiReception,
                    'tone' => 'muted',
                    'href' => route('courriers.index', ['workflow_etape_id' => $byEtape->firstWhere('ordre', 1)?->id]),
                    'show' => $hasReception,
                ],
                [
                    'label' => __('app.kpi_in_progress'),
                    'value' => $kpiInProgress,
                    'tone' => 'accent',
                    'href' => route('courriers.kanban'),
                    'show' => true,
                ],
                [
                    'label' => __('app.kpi_this_month'),
                    'value' => $kpiThisMonth,
                    'tone' => 'brand',
                    'href' => route('courriers.index'),
                    'show' => true,
                ],
            ])->filter(fn ($kpi) => $kpi['show'])->values();
        @endphp

        @foreach ($kpis as $kpi)
            @php
                $tone = match ($kpi['tone']) {
                    'danger' => ['ring' => 'ring-rose-100 border-rose-200', 'value' => 'text-rose-600', 'icon' => 'bg-rose-50 text-rose-600'],
                    'accent' => ['ring' => 'border-gray-200', 'value' => 'text-gray-900', 'icon' => 'bg-[#dc633a]/10 text-[#dc633a]'],
                    'brand' => ['ring' => 'border-gray-200', 'value' => 'text-gray-900', 'icon' => 'bg-[#17384b]/10 text-[#17384b]'],
                    default => ['ring' => 'border-gray-200', 'value' => 'text-gray-900', 'icon' => 'bg-slate-100 text-slate-500'],
                };
            @endphp
            <a href="{{ $kpi['href'] }}"
               class="group rounded-xl border {{ $tone['ring'] }} bg-white p-4 shadow-sm no-underline transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-2">
                    <p class="mb-0 text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ $kpi['label'] }}</p>
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md {{ $tone['icon'] }} opacity-80 transition group-hover:opacity-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-3xl font-bold tracking-tight {{ $tone['value'] }}">
                    {{ number_format($kpi['value']) }}
                </p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Colonne principale --}}
        <div class="space-y-6 xl:col-span-2">
            {{-- Pipeline --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="mb-0 text-sm font-bold text-gray-900">{{ __('app.pipeline_title') }}</h2>
                    <x-buttons.secondary :href="route('courriers.kanban')" class="!px-2.5 !py-1.5 text-xs">
                        {{ __('app.shortcut_kanban') }}
                    </x-buttons.secondary>
                </div>

                @if ($byEtape->sum('total') === 0)
                    <x-empty-state :title="__('app.pipeline_empty')" />
                @else
                    <div class="space-y-3.5">
                        @foreach ($byEtape as $etape)
                            @php $pct = (int) round(($etape->total / $maxEtape) * 100); @endphp
                            <div>
                                <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                    <span class="inline-flex items-center gap-2 font-medium text-gray-800">
                                        <span class="inline-block h-2.5 w-2.5 rounded-full" style="background: {{ $etape->color }}"></span>
                                        {{ $etape->libelle }}
                                    </span>
                                    <span class="font-semibold tabular-nums text-gray-900">{{ $etape->total }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full transition-all duration-700 ease-out"
                                         style="width: {{ $pct }}%; background: {{ $etape->color }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Retards --}}
            <section id="late-panel" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm scroll-mt-24">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <h2 class="mb-0 text-sm font-bold text-gray-900">{{ __('app.late_title') }}</h2>
                    @if ($kpiLate > 0)
                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">
                            {{ $kpiLate }}
                        </span>
                    @endif
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($lateCourriers as $c)
                        <a href="{{ route('courriers.show', $c) }}"
                           class="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 no-underline transition hover:bg-rose-50/40">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ $c->reference_interne }}</span>
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                          style="color: {{ $c->workflowEtape?->ui_color ?? '#64748B' }}; background: {{ ($c->workflowEtape?->ui_color ?? '#64748B') }}18;">
                                        {{ $c->workflowEtape?->libelle ?? '—' }}
                                    </span>
                                </div>
                                <p class="mb-0 mt-1 truncate text-sm text-gray-600">{{ $c->objet }}</p>
                                <p class="mb-0 mt-1 text-xs text-gray-400">
                                    {{ $c->bureauActuel?->nom ?? '—' }}
                                    · {{ $c->assignedTo?->name ?? __('app.assignee') . ' —' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="mb-0 text-[11px] font-bold uppercase tracking-wider text-rose-500">{{ __('app.deadline') }}</p>
                                <p class="mb-0 text-sm font-semibold text-rose-700">
                                    {{ optional($c->date_limite)->format('d/m/Y') ?? '—' }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-8">
                            <x-empty-state :title="__('app.late_empty')" />
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Récents --}}
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <h2 class="mb-0 text-sm font-bold text-gray-900">{{ __('app.recent_title') }}</h2>
                    <x-buttons.secondary :href="route('courriers.index')" class="!px-2.5 !py-1.5 text-xs">
                        {{ __('app.notifications_all') }}
                    </x-buttons.secondary>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">
                                <th class="px-4 py-3">{{ __('courrier.ref') }}</th>
                                <th class="px-4 py-3">{{ __('courrier.subject') }}</th>
                                <th class="px-4 py-3">{{ __('app.etape') }}</th>
                                <th class="px-4 py-3">{{ __('app.assignee') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('app.view') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentCourriers as $c)
                                <tr class="transition hover:bg-gray-50/80">
                                    <td class="px-4 py-3 font-semibold text-gray-900">
                                        <span class="inline-flex items-center gap-1.5">
                                            @if ($c->is_late)
                                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-rose-500" title="{{ __('app.kpi_late') }}"></span>
                                            @endif
                                            {{ $c->reference_interne }}
                                        </span>
                                    </td>
                                    <td class="max-w-xs truncate px-4 py-3 text-gray-700">
                                        {{ \Illuminate\Support\Str::limit($c->objet, 48) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                              style="color: {{ $c->workflowEtape?->ui_color ?? '#64748B' }}; background: {{ ($c->workflowEtape?->ui_color ?? '#64748B') }}18;">
                                            {{ $c->workflowEtape?->libelle ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $c->assignedTo?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <x-buttons.secondary :href="route('courriers.show', $c)" class="!px-2.5 !py-1.5 text-xs">
                                            {{ __('app.view') }}
                                        </x-buttons.secondary>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8">
                                        <x-empty-state :title="__('app.recent_empty')" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- Colonne latérale --}}
        <aside class="space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-sm font-bold text-gray-900">{{ __('app.shortcuts_title') }}</h2>
                <div class="flex flex-col gap-2">
                    @can('courrier.create')
                        <x-buttons.primary :href="route('courriers.create')" class="w-full justify-center">
                            {{ __('app.shortcut_register') }}
                        </x-buttons.primary>
                    @endcan

                    <x-buttons.secondary :href="route('courriers.kanban')" class="w-full justify-center">
                        {{ __('app.shortcut_kanban') }}
                    </x-buttons.secondary>

                    <x-buttons.secondary :href="route('courriers.search')" class="w-full justify-center">
                        {{ __('app.shortcut_search') }}
                    </x-buttons.secondary>

                    @can('chat.view')
                        <a href="{{ route('messages.index') }}"
                           class="relative inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 no-underline transition hover:bg-gray-50">
                            {{ __('app.shortcut_messages') }}
                            @if ($unreadChatCount > 0)
                                <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-[#dc633a] px-1.5 text-[10px] font-bold leading-5 text-white">
                                    {{ $unreadChatCount }}
                                </span>
                            @endif
                        </a>
                    @endcan

                    @role('super-admin|directeur')
                        <x-buttons.secondary :href="route('statistics.index')" class="w-full justify-center">
                            {{ __('app.shortcut_stats') }}
                        </x-buttons.secondary>
                    @endrole

                    @role('super-admin')
                        <x-buttons.secondary :href="route('admin.index')" class="w-full justify-center">
                            {{ __('app.shortcut_admin') }}
                        </x-buttons.secondary>
                    @endrole
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <h2 class="mb-0 text-sm font-bold text-gray-900">{{ __('app.notifications_title') }}</h2>
                    @if ($unreadNotificationsCount > 0)
                        <span class="inline-flex items-center rounded-full bg-[#17384b] px-2 py-0.5 text-[11px] font-bold text-white">
                            {{ $unreadNotificationsCount }}
                        </span>
                    @endif
                </div>

                <div class="space-y-3">
                    @forelse ($unreadNotifications as $n)
                        <div class="rounded-lg border border-gray-200 p-3 transition hover:border-[#17384b]/30">
                            <p class="mb-1 text-sm font-semibold text-gray-900">{{ $n->data['title'] ?? '—' }}</p>
                            <p class="mb-2 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($n->data['message'] ?? '', 90) }}</p>
                            <div class="flex flex-wrap gap-2">
                                @if (! empty($n->data['url']))
                                    <a href="{{ route('notifications.open', $n->id) }}" class="text-xs font-semibold text-[#dc633a] no-underline hover:underline">
                                        {{ __('app.view') }}
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs font-semibold text-gray-400 hover:text-gray-700">
                                        OK
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <x-empty-state :title="__('app.notifications_empty')" />
                    @endforelse
                </div>

                <div class="mt-4">
                    <x-buttons.secondary :href="route('notifications.index')" class="w-full justify-center">
                        {{ __('app.notifications_all') }}
                    </x-buttons.secondary>
                </div>
            </section>

            @if ($monthlyReceived || $byBureau)
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-2">
                        <h2 class="mb-0 text-sm font-bold text-gray-900">{{ __('app.admin_pulse') }}</h2>
                        @isset($treatedThisMonth)
                            <span class="text-xs font-semibold text-gray-500">
                                {{ __('app.treated_month') }}:
                                <span class="text-gray-900">{{ number_format($treatedThisMonth) }}</span>
                            </span>
                        @endisset
                    </div>

                    @if ($monthlyReceived)
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                            {{ __('app.monthly_volume') }}
                        </p>
                        <div class="mb-5 h-36">
                            <canvas id="dashMonthly" aria-label="{{ __('app.monthly_volume') }}"></canvas>
                        </div>
                    @endif

                    @if ($byBureau && $byBureau->sum('total') > 0)
                        <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                            {{ __('app.by_bureau_mini') }}
                        </p>
                        <div class="space-y-2.5">
                            @php $maxBureau = max(1, (int) $byBureau->max('total')); @endphp
                            @foreach ($byBureau->sortByDesc('total')->take(5) as $row)
                                @php $bp = (int) round(($row->total / $maxBureau) * 100); @endphp
                                <div>
                                    <div class="mb-1 flex justify-between gap-2 text-xs">
                                        <span class="truncate font-medium text-gray-700">{{ $row->nom }}</span>
                                        <span class="font-semibold tabular-nums text-gray-900">{{ $row->total }}</span>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full" style="width: {{ $bp }}%; background: {{ $row->color }};"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </aside>
    </div>

    @if ($monthlyReceived)
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const el = document.getElementById('dashMonthly');
                    if (!el) return;

                    const series = @json($monthlyReceived);
                    new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: series.map(s => s.label),
                            datasets: [{
                                data: series.map(s => s.count),
                                backgroundColor: '#17384b',
                                borderRadius: 6,
                                maxBarThickness: 28,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 10 } },
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0, font: { size: 10 } },
                                    grid: { color: '#f3f4f6' },
                                },
                            },
                        },
                    });
                });
            </script>
        @endpush
    @endif
</x-app-layout>
