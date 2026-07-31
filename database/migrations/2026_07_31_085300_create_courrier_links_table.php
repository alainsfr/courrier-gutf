<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courrier_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('courriers')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('courriers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['parent_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courrier_links');
    }
};
