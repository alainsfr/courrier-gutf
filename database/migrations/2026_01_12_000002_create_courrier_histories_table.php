<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courrier_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courrier_id')->constrained('courriers')->cascadeOnDelete();
            $table->string('action', 50);

            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();

            $table->foreignId('actor_user_id')->constrained('users');
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['courrier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courrier_histories');
    }
};
