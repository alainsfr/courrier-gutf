<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'bureau_id')) {
                $table->foreignId('bureau_id')
                    ->nullable()
                    ->after('locale')
                    ->constrained('bureaux')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'bureau_id')) {
                $table->dropConstrainedForeignId('bureau_id');
            }
        });
    }
};
