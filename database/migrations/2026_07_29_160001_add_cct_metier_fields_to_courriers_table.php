<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // `type` existe déjà (varchar) : passage en enum CCT (entrant|sortant|interne)
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE courriers MODIFY COLUMN `type` ENUM('entrant', 'sortant', 'interne') NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE courriers DROP CONSTRAINT IF EXISTS courriers_type_check');
            DB::statement("ALTER TABLE courriers ADD CONSTRAINT courriers_type_check CHECK (type IN ('entrant', 'sortant', 'interne'))");
        }
        // sqlite : varchar reste compatible (validation applicative)

        Schema::table('courriers', function (Blueprint $table) {
            $table->string('tf', 100)->nullable()->after('reference_externe');
            $table->string('proprietaire', 255)->nullable()->after('destinataire');
            $table->string('requerant', 255)->nullable()->after('proprietaire');
            $table->string('vendeur', 255)->nullable()->after('requerant');
            $table->string('etude_notariale', 255)->nullable()->after('vendeur');
            $table->decimal('valeur_immeuble', 15, 2)->nullable()->after('etude_notariale');
            $table->string('numero_acte', 100)->nullable()->after('valeur_immeuble');
            $table->string('numero_repertoire', 100)->nullable()->after('numero_acte');
        });
    }

    public function down(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            $table->dropColumn([
                'tf',
                'proprietaire',
                'requerant',
                'vendeur',
                'etude_notariale',
                'valeur_immeuble',
                'numero_acte',
                'numero_repertoire',
            ]);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE courriers MODIFY COLUMN `type` VARCHAR(10) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE courriers DROP CONSTRAINT IF EXISTS courriers_type_check');
        }
    }
};
