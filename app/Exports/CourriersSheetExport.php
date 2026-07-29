<?php

namespace App\Exports;

use App\Models\Courrier;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Events\AfterSheet;

class CourriersSheetExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithColumnFormatting
{
    public function __construct(private Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            __('courrier.ref'),
            __('courrier.type'),
            __('courrier.subject'),
            __('courrier.filters.etape'),
            __('courrier.sender'),
            __('courrier.recipient'),
            __('courrier.date_received'),
            __('courrier.date_mail'),
            __('courrier.assigned_to'),
            __('courrier.external_ref'),
            __('courrier.attachments'),
        ];
    }

    /**
     * @param Courrier $c
     */
    public function map($c): array
    {
        $assigned = $c->assignedTo ? ($c->assignedTo->name . ' <' . $c->assignedTo->email . '>') : '—';

        $dateReception = $c->date_reception ? ExcelDate::dateTimeToExcel($c->date_reception) : null;
        $dateCourrier  = $c->date_courrier ? ExcelDate::dateTimeToExcel($c->date_courrier) : null;

        return [
            $c->reference_interne,
            strtoupper($c->type),
            $c->objet,
            $c->workflowEtape?->libelle ?? '—',
            $c->expediteur ?? '—',
            $c->destinataire ?? '—',
            $dateReception,
            $dateCourrier,
            $assigned,
            $c->reference_externe ?? '—',
            (int) ($c->attachments_count ?? 0),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'H' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'K' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $sheet->setTitle('Courriers');
                $sheet->freezePane('A2');

                $sheet->getStyle('A1:K1')->getFont()->setBold(true);
                $sheet->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F5F7');
                $sheet->getStyle('A1:K1')->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                $sheet->getStyle('A:K')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $sheet->getStyle('C:C')->getAlignment()->setWrapText(true);
                $sheet->getStyle('I:I')->getAlignment()->setWrapText(true);

                $sheet->getRowDimension(1)->setRowHeight(20);
            },
        ];
    }
}
