<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bureaux')) {
            return;
        }

        Schema::table('bureaux', function (Blueprint $table) {
            if (! Schema::hasColumn('bureaux', 'description')) {
                $table->text('description')->nullable()->after('nom');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bureaux') || ! Schema::hasColumn('bureaux', 'description')) {
            return;
        }

        Schema::table('bureaux', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
