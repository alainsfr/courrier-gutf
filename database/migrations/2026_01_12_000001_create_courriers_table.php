<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courriers', function (Blueprint $table) {
            $table->id();

            $table->string('type', 10); // entrant|sortant
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('seq');
            $table->string('reference_interne', 30)->unique(); // GUTF-ENT-YYYY-000001

            $table->string('reference_externe', 100)->nullable();

            $table->string('objet', 255);
            $table->text('description')->nullable();

            $table->string('expediteur', 255)->nullable();   // entrant
            $table->string('destinataire', 255)->nullable(); // sortant

            $table->date('date_courrier')->nullable();
            $table->date('date_reception')->nullable(); // entrant
            $table->date('date_envoi')->nullable();     // sortant

            $table->string('status', 30)->default('recu'); // recu|affecte|en_traitement|en_validation|valide|rejete|archive...

            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users');

            $table->foreignId('validated_by_user_id')->nullable()->constrained('users');
            $table->timestamp('validated_at')->nullable();

            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            $table->unique(['type', 'year', 'seq']);
            $table->index(['type', 'status']);
            $table->index(['assigned_to_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courriers');
    }
};
