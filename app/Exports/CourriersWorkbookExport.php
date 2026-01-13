<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CourriersWorkbookExport implements WithMultipleSheets
{
    public function __construct(private Collection $rows)
    {
    }

    public function sheets(): array
    {
        return [
            new CourriersSheetExport($this->rows),
            new CourriersSummarySheetExport($this->rows),
        ];
    }
}
