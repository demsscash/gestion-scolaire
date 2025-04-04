<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier si la table academic_sessions existe
        if (Schema::hasTable('academic_sessions')) {
            // Si elle existe, créer une nouvelle table academic_periods avec la même structure
            Schema::create('academic_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('annee_scolaire_id')->constrained()->onDelete('cascade');
                $table->string('libelle');
                $table->date('date_debut');
                $table->date('date_fin');
                $table->boolean('est_cloturee')->default(false);
                $table->timestamps();

                // Une session doit être unique dans une année scolaire
                $table->unique(['annee_scolaire_id', 'libelle']);
            });

            // Copier les données de academic_sessions vers academic_periods si nécessaire
            if (DB::table('academic_sessions')->count() > 0) {
                $sessions = DB::table('academic_sessions')->get();
                foreach ($sessions as $session) {
                    DB::table('academic_periods')->insert([
                        'id' => $session->id,
                        'annee_scolaire_id' => $session->annee_scolaire_id,
                        'libelle' => $session->libelle,
                        'date_debut' => $session->date_debut,
                        'date_fin' => $session->date_fin,
                        'est_cloturee' => $session->est_cloturee,
                        'created_at' => $session->created_at,
                        'updated_at' => $session->updated_at,
                    ]);
                }
            }
        } else {
            // Si la table n'existe pas, créer directement la table academic_periods
            Schema::create('academic_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('annee_scolaire_id')->constrained()->onDelete('cascade');
                $table->string('libelle');
                $table->date('date_debut');
                $table->date('date_fin');
                $table->boolean('est_cloturee')->default(false);
                $table->timestamps();

                // Une session doit être unique dans une année scolaire
                $table->unique(['annee_scolaire_id', 'libelle']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};
