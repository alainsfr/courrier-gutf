<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_pin')->nullable()->after('password');
        });

        Schema::table('parapheur_visas', function (Blueprint $table) {
            $table->string('digital_hash', 64)->nullable()->after('signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_pin');
        });

        Schema::table('parapheur_visas', function (Blueprint $table) {
            $table->dropColumn('digital_hash');
        });
    }
};
