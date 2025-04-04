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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('niveau_id')->constrained();
            $table->foreignId('annee_scolaire_id')->constrained();
            $table->string('nom');
            $table->integer('capacite')->default(30);
            $table->string('titulaire')->nullable();
            $table->timestamps();

            // Une classe doit être unique dans une année scolaire
            $table->unique(['nom', 'annee_scolaire_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
