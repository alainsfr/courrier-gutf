<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bureaux')) {
            Schema::create('bureaux', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('nom', 150);
                $table->string('icon', 80)->default('building-2');
                $table->string('color', 32)->default('#0F766E');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('ordre')->default(0);
                $table->timestamps();
            });

            return;
        }

        Schema::table('bureaux', function (Blueprint $table) {
            if (Schema::hasColumn('bureaux', 'libelle') && ! Schema::hasColumn('bureaux', 'nom')) {
                $table->renameColumn('libelle', 'nom');
            }
        });

        Schema::table('bureaux', function (Blueprint $table) {
            if (Schema::hasColumn('bureaux', 'description')) {
                $table->dropColumn('description');
            }
            if (! Schema::hasColumn('bureaux', 'icon')) {
                $table->string('icon', 80)->default('building-2')->after('nom');
            }
            if (! Schema::hasColumn('bureaux', 'color')) {
                $table->string('color', 32)->default('#0F766E')->after('icon');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bureaux')) {
            return;
        }

        // Ne drop pas la table si créée par une migration antérieure
        Schema::table('bureaux', function (Blueprint $table) {
            if (Schema::hasColumn('bureaux', 'icon')) {
                $table->dropColumn('icon');
            }
            if (Schema::hasColumn('bureaux', 'color')) {
                $table->dropColumn('color');
            }
            if (Schema::hasColumn('bureaux', 'nom') && ! Schema::hasColumn('bureaux', 'libelle')) {
                $table->renameColumn('nom', 'libelle');
            }
        });
    }
};
