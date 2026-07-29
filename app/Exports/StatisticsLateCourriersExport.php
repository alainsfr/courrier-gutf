<?php

namespace App\Exports;

use App\Models\Courrier;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

/**
 * Liste des courriers en retard — FromQuery (chunk Maatwebsite, anti-OOM).
 */
class StatisticsLateCourriersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithTitle, WithColumnFormatting
{
    public function title(): string
    {
        return __('statistics.sheet_late');
    }

    public function query(): Builder
    {
        return Courrier::query()
            ->with(['bureauActuel:id,nom', 'assignedTo:id,name', 'workflowEtape:id,libelle'])
            ->where('is_late', true)
            ->notArchived()
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            __('courrier.ref'),
            __('courrier.subject'),
            __('courrier.filters.etape'),
            __('statistics.bureau'),
            __('courrier.assigned_to'),
            __('statistics.deadline'),
        ];
    }

    /**
     * @param  Courrier  $courrier
     */
    public function map($courrier): array
    {
        return [
            $courrier->reference_interne,
            $courrier->objet,
            $courrier->workflowEtape?->libelle ?? '—',
            $courrier->bureauActuel?->nom ?? '—',
            $courrier->assignedTo?->name ?? '—',
            $courrier->date_limite ? ExcelDate::dateTimeToExcel($courrier->date_limite) : null,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_DATE_DATETIME,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');
                $sheet->getStyle('A1:F1')->getFont()->setBold(true);
                $sheet->getStyle('A1:F1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFF3F5F7');
            },
        ];
    }
}
