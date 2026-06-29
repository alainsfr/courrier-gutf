<?php

namespace App\Exports;

use App\Models\CourrierHistory;
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

class AuditExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithColumnFormatting
{
    /**
     * Récupère tout l'historique d'audit avec les relations requises
     */
    public function collection()
    {
        return CourrierHistory::with(['courrier', 'actor'])->latest()->get();
    }

    /**
     * En-têtes des colonnes Excel
     */
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
     * Association et formatage de chaque ligne d'historique
     * 
     * @param CourrierHistory $history
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

    /**
     * Formatage de la colonne Date & Heure
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DATETIME,
        ];
    }

    /**
     * Événements de mise en page (Entête stylisée, figer la première ligne)
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                // Figer l'en-tête (ligne 1)
                $sheet->freezePane('A2');

                // Style de l'en-tête
                $sheet->getStyle('A1:G1')->getFont()->setBold(true);
                $sheet->getStyle('A1:G1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F5F7');
                $sheet->getStyle('A1:G1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Alignement vertical centré
                $sheet->getStyle('A:G')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                // Retour à la ligne automatique pour les métadonnées JSON complexes
                $sheet->getStyle('G:G')->getAlignment()->setWrapText(true);

                $sheet->getRowDimension(1)->setRowHeight(20);
            },
        ];
    }
}