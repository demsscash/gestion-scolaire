<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnneeScolaireController extends Controller
{
    /**
     * Afficher une liste des années scolaires.
     */
    public function index()
    {
        $anneesScolaires = AnneeScolaire::orderBy('date_debut', 'desc')->get();
        return response()->json($anneesScolaires);
    }

    /**
     * Stocker une nouvelle année scolaire.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:255|unique:annee_scolaires,libelle',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'est_active' => 'boolean',
        ]);

        // Si cette année est marquée comme active, désactiver toutes les autres
        if (isset($validated['est_active']) && $validated['est_active']) {
            AnneeScolaire::where('est_active', true)->update(['est_active' => false]);
        }

        $anneeScolaire = AnneeScolaire::create($validated);

        return response()->json([
            'message' => 'Année scolaire créée avec succès',
            'annee_scolaire' => $anneeScolaire
        ], 201);
    }

    /**
     * Afficher une année scolaire spécifique.
     */
    public function show(string $id)
    {
        $anneeScolaire = AnneeScolaire::with(['sessions', 'classes'])->findOrFail($id);
        return response()->json($anneeScolaire);
    }

    /**
     * Mettre à jour une année scolaire spécifique.
     */
    public function update(Request $request, string $id)
    {
        $anneeScolaire = AnneeScolaire::findOrFail($id);

        $validated = $request->validate([
            'libelle' => 'sometimes|required|string|max:255|unique:annee_scolaires,libelle,' . $id,
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'est_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Si cette année est marquée comme active, désactiver toutes les autres
            if (isset($validated['est_active']) && $validated['est_active']) {
                AnneeScolaire::where('id', '!=', $id)
                    ->where('est_active', true)
                    ->update(['est_active' => false]);
            }

            $anneeScolaire->update($validated);
            DB::commit();

            return response()->json([
                'message' => 'Année scolaire mise à jour avec succès',
                'annee_scolaire' => $anneeScolaire
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de l\'année scolaire',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une année scolaire spécifique.
     */
    public function destroy(string $id)
    {
        $anneeScolaire = AnneeScolaire::findOrFail($id);

        // Vérifier si des sessions, classes ou inscriptions sont associées
        if (
            $anneeScolaire->sessions()->count() > 0 ||
            $anneeScolaire->classes()->count() > 0 ||
            $anneeScolaire->inscriptions()->count() > 0
        ) {
            return response()->json([
                'message' => 'Impossible de supprimer cette année scolaire car des données y sont associées'
            ], 422);
        }

        $anneeScolaire->delete();

        return response()->json([
            'message' => 'Année scolaire supprimée avec succès'
        ]);
    }

    /**
     * Obtenir l'année scolaire active.
     */
    public function getActive()
    {
        $anneeScolaire = AnneeScolaire::where('est_active', true)->first();

        if (!$anneeScolaire) {
            return response()->json([
                'message' => 'Aucune année scolaire active trouvée'
            ], 404);
        }

        return response()->json($anneeScolaire);
    }
}
