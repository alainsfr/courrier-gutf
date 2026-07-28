<?php

namespace App\Exports;

use App\Models\CourrierHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
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

/**
 * Export audit mémoire-safe : FromQuery chunk automatiquement les lignes.
 */
class AuditExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithColumnFormatting
{
    public function __construct(
        private readonly ?Request $request = null
    ) {}

    public function query(): Builder
    {
        // orderBy(id) requis pour un chunking stable (FromQuery)
        return $this->baseQuery()->orderBy('id');
    }

    private function baseQuery(): Builder
    {
        $q = trim((string) ($this->request?->query('q', '') ?? ''));
        $action = (string) ($this->request?->query('action', '') ?? '');
        $dateFrom = (string) ($this->request?->query('date_from', '') ?? '');
        $dateTo = (string) ($this->request?->query('date_to', '') ?? '');

        return CourrierHistory::query()
            ->with(['courrier:id,reference_interne', 'actor:id,name,email'])
            ->when($q !== '', fn ($sub) =>
                $sub->whereHas('courrier', fn ($c) =>
                    $c->where('reference_interne', 'like', "%{$q}%")
                )
            )
            ->when($action !== '', fn ($sub) => $sub->where('action', $action))
            ->when($dateFrom !== '', fn ($sub) => $sub->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($sub) => $sub->whereDate('created_at', '<=', $dateTo));
    }

    public function headings(): array
    {
        return [
            'Date & Heure',
            'Courrier (Référence)',
            'Action effectuée',
            'Statut Précédent',
            'Nouveau Statut',
            'Acteur de l\'action',
            'Métadonnées / Détails',
        ];
    }

    /**
     * @param  CourrierHistory  $history
     */
    public function map($history): array
    {
        $date = $history->created_at ? ExcelDate::dateTimeToExcel($history->created_at) : null;
        $ref = $history->courrier ? $history->courrier->reference_interne : '—';
        $actor = $history->actor ? ($history->actor->name . ' <' . $history->actor->email . '>') : 'Système / Automate';

        $metadataStr = '';
        if ($history->metadata) {
            $metadataStr = json_encode($history->metadata, JSON_UNESCAPED_UNICODE);
        }

        return [
            $date,
            $ref,
            __('audit.actions.' . $history->action) ?? $history->action,
            $history->from_status ? __('courrier.status_label.' . $history->from_status) : '—',
            $history->to_status ? __('courrier.status_label.' . $history->to_status) : '—',
            $actor,
            $metadataStr ?: '—',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DATETIME,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');

                $sheet->getStyle('A1:G1')->getFont()->setBold(true);
                $sheet->getStyle('A1:G1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F5F7');
                $sheet->getStyle('A1:G1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                $sheet->getStyle('A:G')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle('G:G')->getAlignment()->setWrapText(true);

                $sheet->getRowDimension(1)->setRowHeight(20);
            },
        ];
    }
}
