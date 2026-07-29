<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class CourriersSummarySheetExport implements FromArray, ShouldAutoSize, WithEvents
{
    public function __construct(private Collection $rows)
    {
    }

    public function array(): array
    {
        $total = $this->rows->count();
        $withAttachments = $this->rows->filter(fn ($c) => (int)($c->attachments_count ?? 0) > 0)->count();

        $byEtape = $this->rows->groupBy(fn ($c) => $c->workflowEtape?->libelle ?? '—')->map->count()->sortKeys();
        $byType = $this->rows->groupBy('type')->map->count()->sortKeys();

        $byAssignee = $this->rows->groupBy(function ($c) {
            if ($c->assignedTo) {
                return $c->assignedTo->name . ' <' . $c->assignedTo->email . '>';
            }
            return '—';
        })->map->count()->sortKeys();

        $data = [];

        // Title
        $data[] = [__('courrier.export_summary_title')];
        $data[] = [''];

        // Contexte institutionnel (en-tête résumé)
        $data[] = [
            (string) config('institution.country', ''),
            (string) config('institution.motto', ''),
        ];
        $data[] = [(string) config('institution.ministry', '')];
        $data[] = [(string) config('institution.structure', '')];
        $data[] = [''];

        // KPIs
        $data[] = [__('courrier.export_summary_kpis')];
        $data[] = [__('courrier.export_summary_total'), $total];
        $data[] = [__('courrier.export_summary_with_attachments'), $withAttachments];
        $data[] = [''];

        // By étape
        $data[] = [__('courrier.export_summary_by_etape')];
        $data[] = [__('courrier.filters.etape'), __('courrier.export_summary_count')];
        foreach ($byEtape as $label => $count) {
            $data[] = [$label, $count];
        }
        $data[] = [''];

        // By type
        $data[] = [__('courrier.export_summary_by_type')];
        $data[] = [__('courrier.type'), __('courrier.export_summary_count')];
        foreach ($byType as $type => $count) {
            $label = match ($type) {
                'entrant' => __('courrier.type_in'),
                'sortant' => __('courrier.type_out'),
                'interne' => __('courrier.type_internal'),
                default => $type,
            };
            $data[] = [$label, $count];
        }
        $data[] = [''];

        // By assignee
        $data[] = [__('courrier.export_summary_by_assignee')];
        $data[] = [__('courrier.assigned_to'), __('courrier.export_summary_count')];
        foreach ($byAssignee as $who => $count) {
            $data[] = [$who, $count];
        }

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();
                $sheet->setTitle('Résumé');

                // Style title row
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                // Light section headers: rows where column A is a single string and column B empty, excluding blanks
                $highestRow = $sheet->getHighestRow();
                for ($r = 1; $r <= $highestRow; $r++) {
                    $a = (string) $sheet->getCell("A{$r}")->getValue();
                    $b = (string) $sheet->getCell("B{$r}")->getValue();

                    if ($a !== '' && $b === '' && $r !== 1) {
                        $sheet->getStyle("A{$r}:B{$r}")->getFont()->setBold(true);
                        $sheet->getStyle("A{$r}:B{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F5F7');
                    }
                }

                // Style institution lines (rows 3 to 5)
                $sheet->getStyle('A3:B3')->getFont()->setBold(true);
                $sheet->getStyle('A3:B3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAFB');

                $sheet->getStyle('A4:B5')->getFont()->setBold(true);
            },
        ];
    }
}
