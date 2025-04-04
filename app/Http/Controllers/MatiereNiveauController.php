<?php

namespace App\Http\Controllers;

use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\MatiereNiveau;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatiereNiveauController extends Controller
{
    /**
     * Afficher une liste des configurations matière-niveau.
     */
    public function index()
    {
        $matiereNiveaux = MatiereNiveau::with(['matiere', 'niveau'])
            ->orderBy('niveau_id')
            ->orderBy('matiere_id')
            ->get();

        return response()->json($matiereNiveaux);
    }

    /**
     * Stocker une nouvelle configuration matière-niveau.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'matiere_id' => 'required|exists:matieres,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'coefficient' => 'required|integer|min:1|max:10',
        ]);

        // Vérifier si la configuration existe déjà
        $exists = MatiereNiveau::where('matiere_id', $validated['matiere_id'])
            ->where('niveau_id', $validated['niveau_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Cette matière est déjà configurée pour ce niveau'
            ], 422);
        }

        $matiereNiveau = MatiereNiveau::create($validated);

        return response()->json([
            'message' => 'Configuration matière-niveau créée avec succès',
            'matiere_niveau' => $matiereNiveau
        ], 201);
    }

    /**
     * Afficher une configuration matière-niveau spécifique.
     */
    public function show(string $id)
    {
        $matiereNiveau = MatiereNiveau::with(['matiere', 'niveau'])->findOrFail($id);
        return response()->json($matiereNiveau);
    }

    /**
     * Mettre à jour une configuration matière-niveau spécifique.
     */
    public function update(Request $request, string $id)
    {
        $matiereNiveau = MatiereNiveau::findOrFail($id);

        $validated = $request->validate([
            'coefficient' => 'required|integer|min:1|max:10',
        ]);

        $matiereNiveau->update($validated);

        return response()->json([
            'message' => 'Configuration matière-niveau mise à jour avec succès',
            'matiere_niveau' => $matiereNiveau
        ]);
    }

    /**
     * Supprimer une configuration matière-niveau spécifique.
     */
    public function destroy(string $id)
    {
        $matiereNiveau = MatiereNiveau::findOrFail($id);

        // Vérifier si des notes sont associées
        $notesExistent = $matiereNiveau->notes()->exists();

        if ($notesExistent) {
            return response()->json([
                'message' => 'Impossible de supprimer cette configuration car des notes y sont associées'
            ], 422);
        }

        $matiereNiveau->delete();

        return response()->json([
            'message' => 'Configuration matière-niveau supprimée avec succès'
        ]);
    }

    /**
     * Obtenir les configurations matière-niveau par niveau.
     */
    public function getByNiveau(string $niveauId)
    {
        $matiereNiveaux = MatiereNiveau::with('matiere')
            ->where('niveau_id', $niveauId)
            ->orderBy('matiere_id')
            ->get();

        return response()->json($matiereNiveaux);
    }

    /**
     * Ajouter plusieurs matières à un niveau en une seule requête.
     */
    public function addMultiple(Request $request)
    {
        $validated = $request->validate([
            'niveau_id' => 'required|exists:niveaux,id',
            'matieres' => 'required|array',
            'matieres.*.matiere_id' => 'required|exists:matieres,id',
            'matieres.*.coefficient' => 'required|integer|min:1|max:10',
        ]);

        DB::beginTransaction();
        try {
            $niveauId = $validated['niveau_id'];
            $matiereNiveauxAjoutes = [];

            foreach ($validated['matieres'] as $matiere) {
                // Vérifier si la configuration existe déjà
                $exists = MatiereNiveau::where('matiere_id', $matiere['matiere_id'])
                    ->where('niveau_id', $niveauId)
                    ->exists();

                if (!$exists) {
                    $matiereNiveau = MatiereNiveau::create([
                        'matiere_id' => $matiere['matiere_id'],
                        'niveau_id' => $niveauId,
                        'coefficient' => $matiere['coefficient'],
                    ]);

                    $matiereNiveauxAjoutes[] = $matiereNiveau;
                }
            }

            DB::commit();

            return response()->json([
                'message' => count($matiereNiveauxAjoutes) . ' matière(s) ajoutée(s) au niveau avec succès',
                'matiere_niveaux' => $matiereNiveauxAjoutes
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l\'ajout des matières au niveau',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
