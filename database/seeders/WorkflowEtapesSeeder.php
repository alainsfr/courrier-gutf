<?php

namespace Database\Seeders;

use App\Models\Bureau;
use App\Models\WorkflowEtape;
use Illuminate\Database\Seeder;
use RuntimeException;

class WorkflowEtapesSeeder extends Seeder
{
    public function run(): void
    {
        $byCode = Bureau::query()->pluck('id', 'code');

        foreach (['accueil', 'visas_controles', 'livre_foncier', 'enregistrement_fiscal', 'archives'] as $code) {
            if (! isset($byCode[$code])) {
                throw new RuntimeException(
                    "Bureau « {$code} » introuvable. Exécutez BureauSeeder avant WorkflowEtapesSeeder."
                );
            }
        }

        $etapes = [
            [
                'ordre' => 1,
                'libelle' => 'Réception',
                'bureau_code' => 'accueil',
                'delai_jours' => 1,
                'is_rejectable' => false,
                'ui_color' => '#0EA5E9',
                'ui_icon' => 'mail-open',
            ],
            [
                'ordre' => 2,
                'libelle' => 'Analyse',
                'bureau_code' => 'visas_controles',
                'delai_jours' => 2,
                'is_rejectable' => true,
                'ui_color' => '#F59E0B',
                'ui_icon' => 'scan-search',
            ],
            [
                'ordre' => 3,
                'libelle' => 'Traitement',
                'bureau_code' => 'livre_foncier',
                'delai_jours' => 5,
                'is_rejectable' => true,
                'ui_color' => '#6366F1',
                'ui_icon' => 'file-pen',
            ],
            [
                'ordre' => 4,
                'libelle' => 'Validation',
                'bureau_code' => 'visas_controles',
                'delai_jours' => 2,
                'is_rejectable' => true,
                'ui_color' => '#8B5CF6',
                'ui_icon' => 'badge-check',
            ],
            [
                'ordre' => 5,
                'libelle' => 'Sortie',
                'bureau_code' => 'enregistrement_fiscal',
                'delai_jours' => 2,
                'is_rejectable' => false,
                'ui_color' => '#10B981',
                'ui_icon' => 'send',
            ],
            [
                'ordre' => 6,
                'libelle' => 'Archivage',
                'bureau_code' => 'archives',
                'delai_jours' => 1,
                'is_rejectable' => false,
                'ui_color' => '#64748B',
                'ui_icon' => 'archive',
            ],
        ];

        foreach ($etapes as $etape) {
            WorkflowEtape::query()->updateOrCreate(
                ['ordre' => $etape['ordre']],
                [
                    'libelle' => $etape['libelle'],
                    'bureau_id' => $byCode[$etape['bureau_code']],
                    'delai_jours' => $etape['delai_jours'],
                    'is_rejectable' => $etape['is_rejectable'],
                    'ui_color' => $etape['ui_color'],
                    'ui_icon' => $etape['ui_icon'],
                ]
            );
        }
    }
}
