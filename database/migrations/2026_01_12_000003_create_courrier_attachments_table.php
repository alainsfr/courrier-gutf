<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courrier_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courrier_id')->constrained('courriers')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users');

            $table->string('source', 20)->default('upload'); // upload
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('path', 500); // ex: private/courriers/{id}/file.pdf

            $table->timestamps();

            $table->index(['courrier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courrier_attachments');
    }
};
