<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


class InscriptionController extends Controller
{
    /**
     * Afficher une liste de la ressource.
     */
    public function index()
    {
        $inscriptions = Inscription::with(['eleve', 'classe', 'anneeScolaire'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($inscriptions);
    }

    /**
     * Stocker une nouvelle ressource dans le stockage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'classe_id' => 'required|exists:classes,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'date_inscription' => 'required|date',
            'statut' => 'sometimes|in:actif,transféré,retiré',
        ]);

        // Vérifier si l'élève est déjà inscrit pour cette année scolaire
        $existingInscription = Inscription::where('eleve_id', $validated['eleve_id'])
            ->where('annee_scolaire_id', $validated['annee_scolaire_id'])
            ->first();

        if ($existingInscription) {
            return response()->json([
                'message' => 'Cet élève est déjà inscrit pour cette année scolaire',
                'existing_inscription' => $existingInscription
            ], 422);
        }

        $inscription = Inscription::create($validated);

        return response()->json([
            'message' => 'Inscription créée avec succès',
            'inscription' => $inscription
        ], 201);
    }

    /**
     * Afficher la ressource spécifiée.
     */
    public function show(string $id)
    {
        $inscription = Inscription::with(['eleve', 'classe.niveau', 'anneeScolaire'])
            ->findOrFail($id);

        return response()->json($inscription);
    }

    /**
     * Mettre à jour la ressource spécifiée dans le stockage.
     */
    public function update(Request $request, string $id)
    {
        $inscription = Inscription::findOrFail($id);

        $validated = $request->validate([
            'classe_id' => 'sometimes|required|exists:classes,id',
            'date_inscription' => 'sometimes|required|date',
            'statut' => 'sometimes|required|in:actif,transféré,retiré',
        ]);

        $inscription->update($validated);

        return response()->json([
            'message' => 'Inscription mise à jour avec succès',
            'inscription' => $inscription
        ]);
    }

    /**
     * Supprimer la ressource spécifiée du stockage.
     */
    public function destroy(string $id)
    {
        $inscription = Inscription::findOrFail($id);

        // Vérifier si des notes ou bulletins sont associés à cette inscription
        if ($inscription->notes()->count() > 0 || $inscription->bulletins()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer cette inscription car des notes ou bulletins y sont associés'
            ], 422);
        }

        $inscription->delete();

        return response()->json([
            'message' => 'Inscription supprimée avec succès'
        ]);
    }

    /**
     * Obtenir les inscriptions par classe.
     */
    public function getByClasse(string $classeId)
    {
        $inscriptions = Inscription::with(['eleve'])
            ->where('classe_id', $classeId)
            ->orderBy('created_at')
            ->get();

        return response()->json($inscriptions);
    }

    /**
     * Obtenir les inscriptions par élève.
     */
    public function getByEleve(string $eleveId)
    {
        $inscriptions = Inscription::with(['classe.niveau', 'anneeScolaire'])
            ->where('eleve_id', $eleveId)
            ->orderBy('annee_scolaire_id', 'desc')
            ->get();

        return response()->json($inscriptions);
    }

    /**
     * Obtenir les inscriptions par année scolaire.
     */
    public function getByAnneeScolaire(string $anneeScolaireId)
    {
        $inscriptions = Inscription::with(['eleve', 'classe'])
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('classe_id')
            ->paginate(20);

        return response()->json($inscriptions);
    }

    /**
     * Générer une attestation d'inscription.
     */
    public function generateAttestation(string $id)
    {
        $inscription = Inscription::with(['eleve', 'classe.niveau', 'anneeScolaire'])
            ->findOrFail($id);

        $pdf = PDF::loadView('pdf.attestation_inscription', [
            'inscription' => $inscription
        ]);

        $nomFichier = 'attestation_' . $inscription->eleve->nom . '_' . $inscription->eleve->prenom . '.pdf';

        return $pdf->download($nomFichier);
    }

    /**
     * Transférer un élève dans une autre classe.
     */
    public function transferEleve(Request $request, string $id)
    {
        $inscription = Inscription::findOrFail($id);

        $validated = $request->validate([
            'nouvelle_classe_id' => 'required|exists:classes,id',
            'date_transfert' => 'required|date',
            'motif' => 'nullable|string',
        ]);

        // Marquer l'inscription actuelle comme transférée
        $inscription->update([
            'statut' => 'transféré'
        ]);

        // Créer une nouvelle inscription pour la nouvelle classe
        $nouvelleInscription = Inscription::create([
            'eleve_id' => $inscription->eleve_id,
            'classe_id' => $validated['nouvelle_classe_id'],
            'annee_scolaire_id' => $inscription->annee_scolaire_id,
            'date_inscription' => $validated['date_transfert'],
            'statut' => 'actif'
        ]);

        return response()->json([
            'message' => 'Élève transféré avec succès',
            'ancienne_inscription' => $inscription,
            'nouvelle_inscription' => $nouvelleInscription
        ]);
    }
}
