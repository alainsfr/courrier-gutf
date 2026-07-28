<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StatisticsSummarySheetExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
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

    public function title(): string
    {
        return __('statistics.sheet_summary');
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = [__('statistics.export_title')];
        $rows[] = [__('statistics.generated_at'), now()->format('d/m/Y H:i')];
        $rows[] = [''];

        $rows[] = [__('statistics.kpis')];
        $rows[] = [__('statistics.treated_this_month'), $this->data['treatedThisMonth']];
        $rows[] = [__('statistics.late_count'), $this->data['lateCount']];
        $rows[] = [''];

        $rows[] = [__('statistics.by_bureau')];
        $rows[] = [__('statistics.bureau'), __('statistics.count')];
        foreach ($this->data['byBureau'] as $row) {
            $rows[] = [$row->nom, $row->total];
        }
        $rows[] = [''];

        $rows[] = [__('statistics.monthly_received')];
        $rows[] = [__('statistics.month'), __('statistics.count')];
        foreach ($this->data['monthlyReceived'] as $month) {
            $rows[] = [$month['label'], $month['count']];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->freezePane('A2');

                $highestRow = $sheet->getHighestRow();
                for ($r = 1; $r <= $highestRow; $r++) {
                    $a = (string) $sheet->getCell("A{$r}")->getValue();
                    $b = (string) $sheet->getCell("B{$r}")->getValue();

                    if ($a !== '' && $b === '' && $r !== 1) {
                        $sheet->getStyle("A{$r}:B{$r}")->getFont()->setBold(true);
                        $sheet->getStyle("A{$r}:B{$r}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB('FFF3F5F7');
                    }
                }
            },
        ];
    }
}
