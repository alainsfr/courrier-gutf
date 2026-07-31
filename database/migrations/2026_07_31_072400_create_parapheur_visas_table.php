<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parapheur_visas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courrier_id')->constrained('courriers')->cascadeOnDelete();
            $table->foreignId('validator_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['validator_id', 'status']);
            $table->index(['courrier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parapheur_visas');
    }
};
