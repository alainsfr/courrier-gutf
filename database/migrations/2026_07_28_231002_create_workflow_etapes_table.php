<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workflow_etapes')) {
            Schema::create('workflow_etapes', function (Blueprint $table) {
                $table->id();
                $table->string('libelle', 150);
                $table->foreignId('bureau_id')->constrained('bureaux')->restrictOnDelete();
                $table->unsignedSmallInteger('delai_jours')->default(0);
                $table->unsignedSmallInteger('ordre')->unique();
                $table->boolean('is_rejectable')->default(false);
                $table->string('ui_color', 32)->default('#64748B');
                $table->string('ui_icon', 80)->default('circle');
                $table->timestamps();
                $table->index(['ordre', 'bureau_id']);
            });

            return;
        }

        Schema::table('workflow_etapes', function (Blueprint $table) {
            if (Schema::hasColumn('workflow_etapes', 'obligatoire')) {
                $table->dropColumn('obligatoire');
            }
            if (! Schema::hasColumn('workflow_etapes', 'is_rejectable')) {
                $table->boolean('is_rejectable')->default(false)->after('ordre');
            }
            if (! Schema::hasColumn('workflow_etapes', 'ui_color')) {
                $table->string('ui_color', 32)->default('#64748B')->after('is_rejectable');
            }
            if (! Schema::hasColumn('workflow_etapes', 'ui_icon')) {
                $table->string('ui_icon', 80)->default('circle')->after('ui_color');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('workflow_etapes')) {
            return;
        }

        Schema::table('workflow_etapes', function (Blueprint $table) {
            if (Schema::hasColumn('workflow_etapes', 'ui_icon')) {
                $table->dropColumn('ui_icon');
            }
            if (Schema::hasColumn('workflow_etapes', 'ui_color')) {
                $table->dropColumn('ui_color');
            }
            if (Schema::hasColumn('workflow_etapes', 'is_rejectable')) {
                $table->dropColumn('is_rejectable');
            }
            if (! Schema::hasColumn('workflow_etapes', 'obligatoire')) {
                $table->boolean('obligatoire')->default(true)->after('delai_jours');
            }
        });
    }
};
