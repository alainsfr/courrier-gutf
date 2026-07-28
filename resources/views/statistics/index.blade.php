<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="mb-1 text-lg font-bold text-slate-900">{{ __('statistics.title') }}</h1>
                <p class="mb-0 text-sm text-slate-500">{{ __('statistics.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('courrier.export')
                    <a href="{{ route('statistics.export.pdf') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 no-underline hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        {{ __('statistics.export_pdf') }}
                    </a>
                    <a href="{{ route('statistics.export.excel') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white no-underline hover:bg-slate-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        {{ __('statistics.export_excel') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    {{-- KPI cards --}}
    <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                {{ __('statistics.treated_this_month') }}
            </p>
            <p class="mb-1 text-3xl font-bold text-slate-900">{{ number_format($treatedThisMonth) }}</p>
            <p class="mb-0 text-xs text-slate-500">{{ __('statistics.treated_this_month_hint') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                {{ __('statistics.total_in_bureaux') }}
            </p>
            <p class="mb-1 text-3xl font-bold text-slate-900">{{ number_format($byBureau->sum('total')) }}</p>
            <p class="mb-0 text-xs text-slate-500">{{ __('statistics.total_in_bureaux_hint') }}</p>
        </div>

        <div class="rounded-2xl border border-rose-100 bg-rose-50/60 p-5 shadow-sm">
            <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-rose-400">
                {{ __('statistics.late_count') }}
            </p>
            <p class="mb-1 text-3xl font-bold text-rose-700">{{ number_format($lateCount) }}</p>
            <p class="mb-0 text-xs text-rose-500/80">{{ __('statistics.late_count_hint') }}</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-800">{{ __('statistics.chart_bureau') }}</h2>
            <div class="relative mx-auto h-72 max-w-md">
                <canvas id="chartBureau" aria-label="{{ __('statistics.chart_bureau') }}"></canvas>
            </div>
            @if($byBureau->sum('total') === 0)
                <p class="mt-3 mb-0 text-center text-sm text-slate-400">{{ __('statistics.no_data') }}</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-800">{{ __('statistics.chart_monthly') }}</h2>
            <div class="relative h-72">
                <canvas id="chartMonthly" aria-label="{{ __('statistics.chart_monthly') }}"></canvas>
            </div>
        </div>
    </div>

    {{-- Table détail bureaux --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-3">
            <h2 class="mb-0 text-sm font-bold text-slate-800">{{ __('statistics.by_bureau') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-5 py-3">{{ __('statistics.bureau') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('statistics.count') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($byBureau as $row)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-2 font-medium text-slate-800">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full" style="background: {{ $row->color }}"></span>
                                    {{ $row->nom }}
                                    @if($row->code && $row->code !== '—')
                                        <span class="text-xs font-normal text-slate-400">({{ $row->code }})</span>
                                    @endif
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-900">{{ $row->total }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-5 py-8 text-center text-slate-400">{{ __('statistics.no_data') }}</td>
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
                                backgroundColor: '#0f172a',
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
                                    grid: { color: '#f1f5f9' },
                                },
                            },
                        },
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
