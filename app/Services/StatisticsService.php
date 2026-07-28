<?php

namespace App\Services;

use App\Models\Bureau;
use App\Models\Courrier;
use Illuminate\Support\Collection;

class StatisticsService
{
    /**
     * Agrégats KPI + séries pour tableaux de bord et exports.
     *
     * @return array{
     *     treatedThisMonth: int,
     *     lateCount: int,
     *     byBureau: Collection<int, object>,
     *     monthlyReceived: list<array{key: string, label: string, count: int}>
     * }
     */
    public function dashboard(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $treatedThisMonth = Courrier::query()
            ->where('status', 'archive')
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('archived_at', [$monthStart.' 00:00:00', $monthEnd.' 23:59:59'])
                    ->orWhere(function ($fallback) use ($monthStart, $monthEnd) {
                        $fallback->whereNull('archived_at')
                            ->whereBetween('updated_at', [$monthStart.' 00:00:00', $monthEnd.' 23:59:59']);
                    });
            })
            ->count();

        $lateCount = Courrier::query()
            ->where('is_late', true)
            ->where('status', '!=', 'archive')
            ->count();

        $countsByBureau = Courrier::query()
            ->selectRaw('bureau_actuel_id, COUNT(*) as total')
            ->groupBy('bureau_actuel_id')
            ->pluck('total', 'bureau_actuel_id');

        $bureaux = Bureau::query()
            ->orderBy('ordre')
            ->get(['id', 'nom', 'code', 'color']);

        $byBureau = $bureaux->map(function (Bureau $bureau) use ($countsByBureau) {
            return (object) [
                'id' => $bureau->id,
                'nom' => $bureau->nom,
                'code' => $bureau->code,
                'color' => $bureau->color ?: '#64748b',
                'total' => (int) ($countsByBureau[$bureau->id] ?? 0),
            ];
        });

        $unassigned = (int) ($countsByBureau[null] ?? $countsByBureau[''] ?? 0);
        // MySQL/SQLite may key null differently
        if ($unassigned === 0) {
            foreach ($countsByBureau as $key => $total) {
                if ($key === null || $key === '') {
                    $unassigned = (int) $total;
                    break;
                }
            }
        }

        if ($unassigned > 0) {
            $byBureau->push((object) [
                'id' => null,
                'nom' => __('statistics.unassigned_bureau'),
                'code' => '—',
                'color' => '#94a3b8',
                'total' => $unassigned,
            ]);
        }

        return [
            'treatedThisMonth' => $treatedThisMonth,
            'lateCount' => $lateCount,
            'byBureau' => $byBureau,
            'monthlyReceived' => $this->monthlyReceivedSeries(6),
        ];
    }

    /**
     * Courriers reçus par mois (date_reception, sinon created_at) sur N mois glissants.
     *
     * @return list<array{key: string, label: string, count: int}>
     */
    public function monthlyReceivedSeries(int $months = 6): array
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'fr';
        $series = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();
            $key = $start->format('Y-m');

            $count = Courrier::query()
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('date_reception', [$start->toDateString(), $end->toDateString()])
                        ->orWhere(function ($fallback) use ($start, $end) {
                            $fallback->whereNull('date_reception')
                                ->whereBetween('created_at', [
                                    $start->copy()->startOfDay()->toDateTimeString(),
                                    $end->copy()->endOfDay()->toDateTimeString(),
                                ]);
                        });
                })
                ->count();

            $label = $start->locale($locale)->translatedFormat('M Y');

            $series[] = [
                'key' => $key,
                'label' => $label,
                'count' => $count,
            ];
        }

        return $series;
    }
}
