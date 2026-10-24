<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Run before the historical migration that removes `status`.
        // Already upgraded installations and empty databases need no conversion.
        if (! Schema::hasColumn('courriers', 'status') || ! DB::table('courriers')->exists()) {
            return;
        }

        $orders = [
            'recu' => 1,
            'affecte' => 2,
            'en_traitement' => 3,
            'en_validation' => 4,
            'valide' => 5,
            'rejete' => 3,
            'archive' => 6,
        ];

        $statuses = DB::table('courriers')->whereNull('workflow_etape_id')->distinct()->pluck('status')->all();
        if (array_diff($statuses, array_keys($orders))) {
            throw new RuntimeException('Statut de courrier inconnu : conversion interrompue avant suppression des anciens statuts.');
        }

        if (! Schema::hasColumn('courriers', 'legacy_status')) {
            Schema::table('courriers', function (Blueprint $table): void {
                $table->string('legacy_status', 30)->nullable();
            });
        }

        DB::transaction(function () use ($orders): void {
            DB::table('courriers')->whereNull('legacy_status')->update(['legacy_status' => DB::raw('status')]);

            // Frozen reference data: do not overwrite bureaus or steps configured by an administrator.
            $bureaux = [
                'accueil' => ['Accueil', 'inbox', '#0EA5E9', 1],
                'visas_controles' => ['Visas et Contrôles', 'shield-check', '#F59E0B', 2],
                'livre_foncier' => ['Livre Foncier', 'book-marked', '#6366F1', 3],
                'archives' => ['Archives', 'archive', '#64748B', 4],
                'enregistrement_fiscal' => ['Enregistrement Fiscal', 'receipt', '#10B981', 5],
            ];

            foreach ($bureaux as $code => [$name, $icon, $color, $order]) {
                DB::table('bureaux')->insertOrIgnore([
                    'code' => $code, 'nom' => $name, 'icon' => $icon, 'color' => $color,
                    'ordre' => $order, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $bureauIds = DB::table('bureaux')->pluck('id', 'code');
            $steps = [
                1 => ['Réception', 'accueil', 1, false, '#0EA5E9', 'mail-open'],
                2 => ['Analyse', 'visas_controles', 2, true, '#F59E0B', 'scan-search'],
                3 => ['Traitement', 'livre_foncier', 5, true, '#6366F1', 'file-pen'],
                4 => ['Validation', 'visas_controles', 2, true, '#8B5CF6', 'badge-check'],
                5 => ['Sortie', 'enregistrement_fiscal', 2, false, '#10B981', 'send'],
                6 => ['Archivage', 'archives', 1, false, '#64748B', 'archive'],
            ];

            foreach ($steps as $order => [$label, $bureau, $days, $rejectable, $color, $icon]) {
                DB::table('workflow_etapes')->insertOrIgnore([
                    'ordre' => $order, 'libelle' => $label, 'bureau_id' => $bureauIds[$bureau],
                    'delai_jours' => $days, 'is_rejectable' => $rejectable,
                    'ui_color' => $color, 'ui_icon' => $icon, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            foreach ($orders as $status => $order) {
                $step = DB::table('workflow_etapes')->where('ordre', $order)->first();
                DB::table('courriers')->where('status', $status)->whereNull('workflow_etape_id')->update([
                    'workflow_etape_id' => $step->id,
                    'bureau_actuel_id' => $step->bureau_id,
                ]);
            }

            DB::table('courriers')->where('status', 'archive')->whereNull('archived_at')->update([
                'archived_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('courriers', 'status') && Schema::hasColumn('courriers', 'legacy_status')) {
            DB::table('courriers')->whereNotNull('legacy_status')->update(['status' => DB::raw('legacy_status')]);
        }
    }
};
