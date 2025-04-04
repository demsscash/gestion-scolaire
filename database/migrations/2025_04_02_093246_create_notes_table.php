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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained();
            $table->foreignId('matiere_niveau_id')->constrained();
            $table->foreignId('session_id')->constrained();
            $table->decimal('valeur', 5, 2);
            $table->text('appreciation')->nullable();
            $table->date('date_saisie');
            $table->timestamps();

            // Un élève ne peut avoir qu'une note par matière et par session
            $table->unique(['inscription_id', 'matiere_niveau_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
