<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courriers', 'status')) {
            $this->ensureWorkflowIndexes();

            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite refuse de drop une colonne encore référencée par un index.
            $this->dropIndexIfExists('courriers_type_status_index');
            $this->dropIndexIfExists('courriers_assigned_to_user_id_status_index');
        }

        if ($driver === 'mysql') {
            // Garantit un index dédié à la FK avant de retirer l'index composite (…_status).
            $this->ensureMysqlIndex('courriers', 'courriers_assigned_to_user_id_foreign', ['assigned_to_user_id']);
            $this->dropIndexIfExists('courriers_type_status_index');
            $this->dropIndexIfExists('courriers_assigned_to_user_id_status_index');
        }

        Schema::table('courriers', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        $this->ensureWorkflowIndexes();
    }

    public function down(): void
    {
        $this->dropIndexIfExists('courriers_type_workflow_etape_id_index');
        $this->dropIndexIfExists('courriers_assigned_to_user_id_workflow_etape_id_index');

        if (! Schema::hasColumn('courriers', 'status')) {
            Schema::table('courriers', function (Blueprint $table) {
                $table->string('status', 30)->default('recu')->after('date_envoi');
            });
        }

        Schema::table('courriers', function (Blueprint $table) {
            $table->index(['type', 'status']);
            $table->index(['assigned_to_user_id', 'status']);
        });
    }

    private function ensureWorkflowIndexes(): void
    {
        $this->ensureMysqlIndex('courriers', 'courriers_type_workflow_etape_id_index', ['type', 'workflow_etape_id']);
        $this->ensureMysqlIndex('courriers', 'courriers_assigned_to_user_id_workflow_etape_id_index', ['assigned_to_user_id', 'workflow_etape_id']);
    }

    private function dropIndexIfExists(string $index): void
    {
        try {
            Schema::table('courriers', function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        } catch (\Throwable) {
            // Index déjà absent
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function ensureMysqlIndex(string $table, string $index, array $columns): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
                    $blueprint->index($columns, $index);
                });
            } catch (\Throwable) {
            }

            return;
        }

        if ($driver !== 'mysql') {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
                $blueprint->index($columns, $index);
            });

            return;
        }

        $exists = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index]
        );

        if ($exists) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
            $blueprint->index($columns, $index);
        });
    }
};
