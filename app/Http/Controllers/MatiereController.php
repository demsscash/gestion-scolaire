<?php

namespace App\Http\Controllers;

use App\Models\Matiere;
use Illuminate\Http\Request;

class MatiereController extends Controller
{
    /**
     * Afficher une liste des matières.
     */
    public function index()
    {
        $matieres = Matiere::orderBy('libelle')->get();
        return response()->json($matieres);
    }

    /**
     * Stocker une nouvelle matière.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:matieres,code',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $matiere = Matiere::create($validated);

        return response()->json([
            'message' => 'Matière créée avec succès',
            'matiere' => $matiere
        ], 201);
    }

    /**
     * Afficher une matière spécifique.
     */
    public function show(string $id)
    {
        $matiere = Matiere::with(['niveaux', 'matiereNiveaux'])->findOrFail($id);
        return response()->json($matiere);
    }

    /**
     * Mettre à jour une matière spécifique.
     */
    public function update(Request $request, string $id)
    {
        $matiere = Matiere::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:10|unique:matieres,code,' . $id,
            'libelle' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $matiere->update($validated);

        return response()->json([
            'message' => 'Matière mise à jour avec succès',
            'matiere' => $matiere
        ]);
    }

    /**
     * Supprimer une matière spécifique.
     */
    public function destroy(string $id)
    {
        $matiere = Matiere::findOrFail($id);

        // Vérifier si des configurations matière-niveau sont associées
        if ($matiere->matiereNiveaux()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer cette matière car elle est configurée pour certains niveaux'
            ], 422);
        }

        $matiere->delete();

        return response()->json([
            'message' => 'Matière supprimée avec succès'
        ]);
    }

    /**
     * Obtenir les niveaux associés à une matière.
     */
    public function getNiveaux(string $id)
    {
        $matiere = Matiere::findOrFail($id);
        $niveaux = $matiere->niveaux;

        return response()->json($niveaux);
    }
}
