<?php

namespace Database\Seeders;

use App\Models\Bureau;
use Illuminate\Database\Seeder;

class BureauSeeder extends Seeder
{
    public function run(): void
    {
        $bureaux = [
            [
                'code' => 'accueil',
                'nom' => 'Accueil',
                'icon' => 'inbox',
                'color' => '#0EA5E9',
                'ordre' => 1,
            ],
            [
                'code' => 'visas_controles',
                'nom' => 'Visas et Contrôles',
                'icon' => 'shield-check',
                'color' => '#F59E0B',
                'ordre' => 2,
            ],
            [
                'code' => 'livre_foncier',
                'nom' => 'Livre Foncier',
                'icon' => 'book-marked',
                'color' => '#6366F1',
                'ordre' => 3,
            ],
            [
                'code' => 'archives',
                'nom' => 'Archives',
                'icon' => 'archive',
                'color' => '#64748B',
                'ordre' => 4,
            ],
            [
                'code' => 'enregistrement_fiscal',
                'nom' => 'Enregistrement Fiscal',
                'icon' => 'receipt',
                'color' => '#10B981',
                'ordre' => 5,
            ],
        ];

        foreach ($bureaux as $bureau) {
            Bureau::query()->updateOrCreate(
                ['code' => $bureau['code']],
                [
                    'nom' => $bureau['nom'],
                    'icon' => $bureau['icon'],
                    'color' => $bureau['color'],
                    'ordre' => $bureau['ordre'],
                    'is_active' => true,
                ]
            );
        }
    }
}
