<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StatisticsWorkbookExport implements WithMultipleSheets
{
    /**
     * @param  array{
     *     treatedThisMonth: int,
     *     lateCount: int,
     *     byBureau: \Illuminate\Support\Collection,
     *     monthlyReceived: list<array{key: string, label: string, count: int}>
     * }  $data
     */
    public function __construct(
        private readonly array $data
    ) {}

    public function sheets(): array
    {
        return [
            new StatisticsSummarySheetExport($this->data),
            new StatisticsLateCourriersExport(),
        ];
    }
}
