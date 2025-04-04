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
        Schema::create('matiere_niveaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained();
            $table->foreignId('niveau_id')->constrained();
            $table->integer('coefficient')->default(1);
            $table->timestamps();

            // Une matière ne peut être associée qu'une fois à un niveau
            $table->unique(['matiere_id', 'niveau_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matiere_niveaux');
    }
};
