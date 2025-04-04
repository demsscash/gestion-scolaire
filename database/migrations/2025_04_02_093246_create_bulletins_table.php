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
        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained();
            $table->foreignId('session_id')->constrained();
            $table->decimal('moyenne_generale', 5, 2);
            $table->integer('rang');
            $table->text('appreciation_generale')->nullable();
            $table->date('date_edition');
            $table->enum('decision', ['passage', 'redoublement', 'en_attente'])->default('en_attente');
            $table->timestamps();

            // Un élève ne peut avoir qu'un bulletin par session
            $table->unique(['inscription_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulletins');
    }
};
