<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained();
            $table->foreignId('classe_id')->constrained();
            $table->foreignId('annee_scolaire_id')->constrained();
            $table->date('date_inscription');
            $table->enum('statut', ['actif', 'transféré', 'retiré'])->default('actif');
            $table->timestamps();

            // Un élève ne peut être inscrit qu'une seule fois dans une année scolaire
            $table->unique(['eleve_id', 'annee_scolaire_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
