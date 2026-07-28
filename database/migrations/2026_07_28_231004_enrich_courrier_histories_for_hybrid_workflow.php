<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courrier_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('courrier_histories', 'ancienne_etape_id')) {
                $table->foreignId('ancienne_etape_id')
                    ->nullable()
                    ->after('to_status')
                    ->constrained('workflow_etapes')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('courrier_histories', 'nouvelle_etape_id')) {
                $table->foreignId('nouvelle_etape_id')
                    ->nullable()
                    ->after('ancienne_etape_id')
                    ->constrained('workflow_etapes')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('courrier_histories', 'note_metier')) {
                $table->text('note_metier')->nullable()->after('nouvelle_etape_id');
            }

            if (! Schema::hasColumn('courrier_histories', 'action_type')) {
                $table->string('action_type', 20)
                    ->nullable()
                    ->after('note_metier')
                    ->comment('forward|backward|reject');
                $table->index('action_type');
            }

            // Ancien champ de la V1 workflow (remplacé par action_type côté CCT hybride)
            if (Schema::hasColumn('courrier_histories', 'fichier_joint')) {
                $table->dropColumn('fichier_joint');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courrier_histories', function (Blueprint $table) {
            if (Schema::hasColumn('courrier_histories', 'action_type')) {
                $table->dropIndex(['action_type']);
                $table->dropColumn('action_type');
            }
            if (! Schema::hasColumn('courrier_histories', 'fichier_joint')) {
                $table->string('fichier_joint', 500)->nullable()->after('note_metier');
            }
        });
    }
};
