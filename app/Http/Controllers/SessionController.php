<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * Afficher une liste des sessions.
     */
    public function index()
    {
        $sessions = Session::with('anneeScolaire')
            ->orderBy('date_debut', 'desc')
            ->get();

        return response()->json($sessions);
    }

    /**
     * Stocker une nouvelle session.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'libelle' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'est_cloturee' => 'boolean',
        ]);

        // Vérifier l'unicité de la session dans l'année scolaire
        $exists = Session::where('annee_scolaire_id', $validated['annee_scolaire_id'])
            ->where('libelle', $validated['libelle'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Une session avec ce libellé existe déjà pour cette année scolaire'
            ], 422);
        }

        $session = Session::create($validated);

        return response()->json([
            'message' => 'Session créée avec succès',
            'session' => $session
        ], 201);
    }

    /**
     * Afficher une session spécifique.
     */
    public function show(string $id)
    {
        $session = Session::with('anneeScolaire')->findOrFail($id);
        return response()->json($session);
    }

    /**
     * Mettre à jour une session spécifique.
     */
    public function update(Request $request, string $id)
    {
        $session = Session::findOrFail($id);

        $validated = $request->validate([
            'libelle' => 'sometimes|required|string|max:255',
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'est_cloturee' => 'boolean',
        ]);

        // Vérifier l'unicité du libellé si modifié
        if (isset($validated['libelle']) && $validated['libelle'] !== $session->libelle) {
            $exists = Session::where('annee_scolaire_id', $session->annee_scolaire_id)
                ->where('libelle', $validated['libelle'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Une session avec ce libellé existe déjà pour cette année scolaire'
                ], 422);
            }
        }

        $session->update($validated);

        return response()->json([
            'message' => 'Session mise à jour avec succès',
            'session' => $session
        ]);
    }

    /**
     * Supprimer une session spécifique.
     */
    public function destroy(string $id)
    {
        $session = Session::findOrFail($id);

        // Vérifier si des notes ou bulletins sont associés
        if ($session->notes()->count() > 0 || $session->bulletins()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer cette session car des notes ou bulletins y sont associés'
            ], 422);
        }

        $session->delete();

        return response()->json([
            'message' => 'Session supprimée avec succès'
        ]);
    }

    /**
     * Obtenir les sessions par année scolaire.
     */
    public function getByAnneeScolaire(string $anneeScolaireId)
    {
        $sessions = Session::where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('date_debut')
            ->get();

        return response()->json($sessions);
    }

    /**
     * Clôturer une session.
     */
    public function cloturer(string $id)
    {
        $session = Session::findOrFail($id);
        $session->update(['est_cloturee' => true]);

        return response()->json([
            'message' => 'Session clôturée avec succès',
            'session' => $session
        ]);
    }

    /**
     * Rouvrir une session.
     */
    public function rouvrir(string $id)
    {
        $session = Session::findOrFail($id);
        $session->update(['est_cloturee' => false]);

        return response()->json([
            'message' => 'Session rouverte avec succès',
            'session' => $session
        ]);
    }
}
