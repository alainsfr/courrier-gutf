<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            if (! Schema::hasColumn('courriers', 'date_limite')) {
                $table->timestamp('date_limite')->nullable()->after('archived_at');
            }
            if (! Schema::hasColumn('courriers', 'is_late')) {
                $table->boolean('is_late')->default(false)->after('date_limite');
            }

            $table->index(['is_late', 'date_limite']);
        });
    }

    public function down(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            if (Schema::hasColumn('courriers', 'is_late')) {
                $table->dropColumn('is_late');
            }
            if (Schema::hasColumn('courriers', 'date_limite')) {
                $table->dropColumn('date_limite');
            }
        });
    }
};
