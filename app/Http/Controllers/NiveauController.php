<?php

namespace App\Http\Controllers;

use App\Models\Niveau;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;

class NiveauController extends Controller
{
    /**
     * Afficher une liste des niveaux.
     */
    public function index()
    {
        $niveaux = Niveau::orderBy('code')->get();
        return response()->json($niveaux);
    }

    /**
     * Stocker un nouveau niveau.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:niveaux,code',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frais_scolarite' => 'nullable|numeric|min:0',
        ]);

        $niveau = Niveau::create($validated);

        return response()->json([
            'message' => 'Niveau créé avec succès',
            'niveau' => $niveau
        ], 201);
    }

    /**
     * Afficher un niveau spécifique.
     */
    public function show(string $id)
    {
        $niveau = Niveau::with(['matiereNiveaux.matiere'])->findOrFail($id);

        // Obtenir les classes pour l'année scolaire active
        $anneeScolaireActive = AnneeScolaire::where('est_active', true)->first();

        if ($anneeScolaireActive) {
            $niveau->classes = $niveau->classes()
                ->where('annee_scolaire_id', $anneeScolaireActive->id)
                ->get();
        }

        return response()->json($niveau);
    }

    /**
     * Mettre à jour un niveau spécifique.
     */
    public function update(Request $request, string $id)
    {
        $niveau = Niveau::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:10|unique:niveaux,code,' . $id,
            'libelle' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'frais_scolarite' => 'nullable|numeric|min:0',
        ]);

        $niveau->update($validated);

        return response()->json([
            'message' => 'Niveau mis à jour avec succès',
            'niveau' => $niveau
        ]);
    }

    /**
     * Supprimer un niveau spécifique.
     */
    public function destroy(string $id)
    {
        $niveau = Niveau::findOrFail($id);

        // Vérifier si des classes ou des matière-niveaux sont associés
        if ($niveau->classes()->count() > 0 || $niveau->matiereNiveaux()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer ce niveau car des classes ou des matières y sont associées'
            ], 422);
        }

        $niveau->delete();

        return response()->json([
            'message' => 'Niveau supprimé avec succès'
        ]);
    }

    /**
     * Obtenir les matières associées à un niveau.
     */
    public function getMatieres(string $id)
    {
        $niveau = Niveau::findOrFail($id);
        $matieres = $niveau->matiereNiveaux()->with('matiere')->get();

        return response()->json($matieres);
    }

    /**
     * Obtenir les classes associées à un niveau pour l'année scolaire active.
     */
    public function getClasses(string $id)
    {
        $niveau = Niveau::findOrFail($id);

        $anneeScolaireActive = AnneeScolaire::where('est_active', true)->first();

        if (!$anneeScolaireActive) {
            return response()->json([
                'message' => 'Aucune année scolaire active trouvée'
            ], 404);
        }

        $classes = $niveau->classes()
            ->where('annee_scolaire_id', $anneeScolaireActive->id)
            ->orderBy('nom')
            ->get();

        return response()->json($classes);
    }
}
