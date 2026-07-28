<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            if (! Schema::hasColumn('courriers', 'bureau_actuel_id')) {
                $table->foreignId('bureau_actuel_id')
                    ->nullable()
                    ->after('assigned_to_user_id')
                    ->constrained('bureaux')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('courriers', 'workflow_etape_id')) {
                $table->foreignId('workflow_etape_id')
                    ->nullable()
                    ->after('bureau_actuel_id')
                    ->constrained('workflow_etapes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            if (Schema::hasColumn('courriers', 'workflow_etape_id')) {
                $table->dropConstrainedForeignId('workflow_etape_id');
            }
            if (Schema::hasColumn('courriers', 'bureau_actuel_id')) {
                $table->dropConstrainedForeignId('bureau_actuel_id');
            }
        });
    }
};
