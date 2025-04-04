<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Niveau;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;

class ClasseController extends Controller
{
    /**
     * Afficher une liste des classes.
     */
    public function index()
    {
        $classes = Classe::with(['niveau', 'anneeScolaire'])
            ->orderBy('nom')
            ->get();

        return response()->json($classes);
    }

    /**
     * Stocker une nouvelle classe.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'niveau_id' => 'required|exists:niveaux,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'nom' => 'required|string|max:255',
            'capacite' => 'nullable|integer|min:1|max:100',
            'titulaire' => 'nullable|string|max:255',
        ]);

        // Vérifier l'unicité du nom de classe dans l'année scolaire
        $exists = Classe::where('nom', $validated['nom'])
            ->where('annee_scolaire_id', $validated['annee_scolaire_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Une classe avec ce nom existe déjà pour cette année scolaire'
            ], 422);
        }

        $classe = Classe::create($validated);

        return response()->json([
            'message' => 'Classe créée avec succès',
            'classe' => $classe
        ], 201);
    }

    /**
     * Afficher une classe spécifique.
     */
    public function show(string $id)
    {
        $classe = Classe::with(['niveau', 'anneeScolaire', 'inscriptions.eleve'])
            ->findOrFail($id);

        // Calculer l'effectif réel (inscriptions actives)
        $effectifReel = $classe->inscriptions()->where('statut', 'actif')->count();

        // Ajouter l'effectif aux données de la classe
        $classe->effectif_reel = $effectifReel;

        return response()->json($classe);
    }

    /**
     * Mettre à jour une classe spécifique.
     */
    public function update(Request $request, string $id)
    {
        $classe = Classe::findOrFail($id);

        $validated = $request->validate([
            'niveau_id' => 'sometimes|required|exists:niveaux,id',
            'nom' => 'sometimes|required|string|max:255',
            'capacite' => 'nullable|integer|min:1|max:100',
            'titulaire' => 'nullable|string|max:255',
        ]);

        // Vérifier l'unicité du nom si modifié
        if (isset($validated['nom']) && $validated['nom'] !== $classe->nom) {
            $exists = Classe::where('nom', $validated['nom'])
                ->where('annee_scolaire_id', $classe->annee_scolaire_id)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Une classe avec ce nom existe déjà pour cette année scolaire'
                ], 422);
            }
        }

        $classe->update($validated);

        return response()->json([
            'message' => 'Classe mise à jour avec succès',
            'classe' => $classe
        ]);
    }

    /**
     * Supprimer une classe spécifique.
     */
    public function destroy(string $id)
    {
        $classe = Classe::findOrFail($id);

        // Vérifier si des inscriptions sont associées
        if ($classe->inscriptions()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer cette classe car des élèves y sont inscrits'
            ], 422);
        }

        $classe->delete();

        return response()->json([
            'message' => 'Classe supprimée avec succès'
        ]);
    }

    /**
     * Obtenir les classes par année scolaire.
     */
    public function getByAnneeScolaire(string $anneeScolaireId)
    {
        $classes = Classe::with('niveau')
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('nom')
            ->get();

        return response()->json($classes);
    }

    /**
     * Obtenir les classes par niveau.
     */
    public function getByNiveau(string $niveauId)
    {
        // Obtenir l'année scolaire active par défaut
        $anneeScolaireActive = AnneeScolaire::where('est_active', true)->first();

        if (!$anneeScolaireActive) {
            return response()->json([
                'message' => 'Aucune année scolaire active trouvée'
            ], 404);
        }

        $classes = Classe::where('niveau_id', $niveauId)
            ->where('annee_scolaire_id', $anneeScolaireActive->id)
            ->orderBy('nom')
            ->get();

        return response()->json($classes);
    }

    /**
     * Obtenir l'effectif de la classe.
     */
    public function getEffectif(string $id)
    {
        $classe = Classe::findOrFail($id);

        $effectif = [
            'total' => $classe->inscriptions()->count(),
            'actifs' => $classe->inscriptions()->where('statut', 'actif')->count(),
            'transferes' => $classe->inscriptions()->where('statut', 'transféré')->count(),
            'retires' => $classe->inscriptions()->where('statut', 'retiré')->count(),
            'capacite' => $classe->capacite,
            'places_disponibles' => $classe->capacite - $classe->inscriptions()->where('statut', 'actif')->count()
        ];

        return response()->json([
            'classe' => $classe->nom,
            'effectif' => $effectif
        ]);
    }
}
