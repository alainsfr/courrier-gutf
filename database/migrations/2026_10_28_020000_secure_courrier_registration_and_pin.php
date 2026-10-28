<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courrier_sequences', function (Blueprint $table) {
            $table->string('type', 10);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_seq')->default(0);
            $table->primary(['type', 'year']);
        });

        Schema::table('courriers', function (Blueprint $table) {
            $table->uuid('submission_key')->nullable()->unique();
            $table->string('submission_hash', 64)->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('signature_pin_attempts')->default(0);
            $table->timestamp('signature_pin_locked_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signature_pin_attempts', 'signature_pin_locked_until']);
        });
        Schema::table('courriers', function (Blueprint $table) {
            $table->dropUnique(['submission_key']);
            $table->dropColumn(['submission_key', 'submission_hash']);
        });
        Schema::dropIfExists('courrier_sequences');
    }
};
