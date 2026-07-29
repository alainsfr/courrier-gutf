<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="mb-1 text-lg font-bold text-gray-900">{{ __('statistics.title') }}</h1>
                <p class="mb-0 text-sm text-gray-500">{{ __('statistics.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('courrier.export')
                    <x-buttons.secondary :href="route('statistics.export.pdf')">
                        {{ __('statistics.export_pdf') }}
                    </x-buttons.secondary>
                    <x-buttons.primary :href="route('statistics.export.excel')">
                        {{ __('statistics.export_excel') }}
                    </x-buttons.primary>
                @endcan
            </div>
        </div>
    </x-slot>

    {{-- KPI cards unifiées --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                        {{ __('statistics.treated_this_month') }}
                    </p>
                    <p class="mb-1 text-3xl font-bold tracking-tight text-gray-900">{{ number_format($treatedThisMonth) }}</p>
                    <p class="mb-0 text-xs text-gray-500">{{ __('statistics.treated_this_month_hint') }}</p>
                </div>
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#17384b]/10 text-[#17384b]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                        {{ __('statistics.total_in_bureaux') }}
                    </p>
                    <p class="mb-1 text-3xl font-bold tracking-tight text-gray-900">{{ number_format($byBureau->sum('total')) }}</p>
                    <p class="mb-0 text-xs text-gray-500">{{ __('statistics.total_in_bureaux_hint') }}</p>
                </div>
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#17384b]/10 text-[#17384b]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                        {{ __('statistics.late_count') }}
                    </p>
                    <p class="mb-1 text-3xl font-bold tracking-tight {{ $lateCount > 0 ? 'text-rose-600' : 'text-gray-900' }}">
                        {{ number_format($lateCount) }}
                    </p>
                    <p class="mb-0 text-xs text-gray-500">{{ __('statistics.late_count_hint') }}</p>
                </div>
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $lateCount > 0 ? 'bg-rose-50 text-rose-600' : 'bg-[#17384b]/10 text-[#17384b]' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-sm font-bold text-gray-800">{{ __('statistics.chart_bureau') }}</h2>
            <div class="relative mx-auto h-72 max-w-md">
                <canvas id="chartBureau" aria-label="{{ __('statistics.chart_bureau') }}"></canvas>
            </div>
            @if($byBureau->sum('total') === 0)
                <p class="mt-3 mb-0 text-center text-sm text-gray-400">{{ __('statistics.no_data') }}</p>
            @endif
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-sm font-bold text-gray-800">{{ __('statistics.chart_monthly') }}</h2>
            <div class="relative h-72">
                <canvas id="chartMonthly" aria-label="{{ __('statistics.chart_monthly') }}"></canvas>
            </div>
        </div>
    </div>

    {{-- Table détail bureaux --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-3.5">
            <h2 class="mb-0 text-sm font-bold text-gray-800">{{ __('statistics.by_bureau') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    <tr>
                        <th class="px-5 py-3">{{ __('statistics.bureau') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('statistics.count') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($byBureau as $row)
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-2 font-medium text-gray-800">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full" style="background: {{ $row->color }}"></span>
                                    {{ $row->nom }}
                                    @if($row->code && $row->code !== '—')
                                        <span class="text-xs font-normal text-gray-400">({{ $row->code }})</span>
                                    @endif
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900">{{ $row->total }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-5 py-10 text-center text-gray-400">{{ __('statistics.no_data') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const bureauLabels = @json($chartBureauLabels);
                const bureauValues = @json($chartBureauValues);
                const bureauColors = @json($chartBureauColors);
                const monthLabels = @json($chartMonthLabels);
                const monthValues = @json($chartMonthValues);

                const pieEl = document.getElementById('chartBureau');
                if (pieEl && bureauValues.some(v => Number(v) > 0)) {
                    new Chart(pieEl, {
                        type: 'pie',
                        data: {
                            labels: bureauLabels,
                            datasets: [{
                                data: bureauValues,
                                backgroundColor: bureauColors,
                                borderWidth: 2,
                                borderColor: '#fff',
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { boxWidth: 12, padding: 14, font: { size: 11 } },
                                },
                            },
                        },
                    });
                }

                const barEl = document.getElementById('chartMonthly');
                if (barEl) {
                    new Chart(barEl, {
                        type: 'bar',
                        data: {
                            labels: monthLabels,
                            datasets: [{
                                label: @json(__('statistics.count')),
                                data: monthValues,
                                backgroundColor: '#17384b',
                                borderRadius: 8,
                                maxBarThickness: 48,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 11 } },
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0, font: { size: 11 } },
                                    grid: { color: '#f3f4f6' },
                                },
                            },
                        },
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
