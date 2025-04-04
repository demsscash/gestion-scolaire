<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Inscription;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FactureController extends Controller
{
    /**
     * Afficher une liste des factures.
     */
    public function index(Request $request)
    {
        $query = Facture::with(['inscription.eleve', 'inscription.classe']);

        // Filtrage par statut
        if ($request->has('statut') && in_array($request->statut, ['payée', 'partiellement_payée', 'impayée'])) {
            $query->where('statut', $request->statut);
        }

        // Filtrage par date
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('date_emission', [$request->date_debut, $request->date_fin]);
        }

        $factures = $query->orderBy('date_emission', 'desc')->paginate(15);

        return response()->json($factures);
    }

    /**
     * Stocker une nouvelle facture.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'numero' => 'required|string|max:50|unique:factures,numero',
            'date_emission' => 'required|date',
            'montant_total' => 'required|numeric|min:1',
            'details' => 'nullable|array',
            'details.*.libelle' => 'required|string|max:255',
            'details.*.montant' => 'required|numeric|min:0',
            'details.*.quantite' => 'required|integer|min:1',
        ]);

        // Vérifier que l'inscription existe et est active
        $inscription = Inscription::findOrFail($validated['inscription_id']);

        if ($inscription->statut !== 'actif') {
            return response()->json([
                'message' => 'Impossible de créer une facture pour un élève dont l\'inscription n\'est pas active'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Créer la facture
            $facture = Facture::create([
                'inscription_id' => $validated['inscription_id'],
                'numero' => $validated['numero'],
                'date_emission' => $validated['date_emission'],
                'montant_total' => $validated['montant_total'],
                'statut' => 'impayée',
            ]);

            // Si des détails de facture sont fournis, les stocker
            if (isset($validated['details']) && !empty($validated['details'])) {
                // Implémenter le stockage des détails de facture si nécessaire
                // (nécessiterait un modèle FactureDetail et une migration associée)
            }

            DB::commit();

            return response()->json([
                'message' => 'Facture créée avec succès',
                'facture' => $facture
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la création de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une facture spécifique.
     */
    public function show(string $id)
    {
        $facture = Facture::with(['inscription.eleve', 'inscription.classe', 'paiements'])->findOrFail($id);

        // Calculer le montant total des paiements associés
        $totalPaiements = $facture->paiements()->sum('montant');
        $facture->montant_paye = $totalPaiements;
        $facture->montant_restant = $facture->montant_total - $totalPaiements;

        return response()->json($facture);
    }

    /**
     * Mettre à jour une facture spécifique.
     */
    public function update(Request $request, string $id)
    {
        $facture = Facture::findOrFail($id);

        // Empêcher la modification si des paiements sont associés
        if ($facture->paiements()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de modifier une facture ayant des paiements associés'
            ], 422);
        }

        $validated = $request->validate([
            'numero' => 'sometimes|required|string|max:50|unique:factures,numero,' . $id,
            'date_emission' => 'sometimes|required|date',
            'montant_total' => 'sometimes|required|numeric|min:1',
        ]);

        $facture->update($validated);

        return response()->json([
            'message' => 'Facture mise à jour avec succès',
            'facture' => $facture
        ]);
    }

    /**
     * Supprimer une facture spécifique.
     */
    public function destroy(string $id)
    {
        $facture = Facture::findOrFail($id);

        // Empêcher la suppression si des paiements sont associés
        if ($facture->paiements()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer une facture ayant des paiements associés'
            ], 422);
        }

        $facture->delete();

        return response()->json([
            'message' => 'Facture supprimée avec succès'
        ]);
    }

    /**
     * Obtenir les factures par inscription.
     */
    public function getByInscription(string $inscriptionId)
    {
        $factures = Facture::where('inscription_id', $inscriptionId)
            ->orderBy('date_emission', 'desc')
            ->get();

        return response()->json($factures);
    }

    /**
     * Générer une facture au format PDF.
     */
    public function generatePdf(string $id)
    {
        $facture = Facture::with([
            'inscription.eleve',
            'inscription.classe.niveau',
            'inscription.anneeScolaire',
            'paiements'
        ])->findOrFail($id);

        // Calculer le montant total des paiements associés
        $totalPaiements = $facture->paiements()->sum('montant');
        $facture->montant_paye = $totalPaiements;
        $facture->montant_restant = $facture->montant_total - $totalPaiements;

        $pdf = PDF::loadView('pdf.facture', [
            'facture' => $facture
        ]);

        $nomFichier = 'facture_' . $facture->numero . '.pdf';

        return $pdf->download($nomFichier);
    }

    /**
     * Marquer une facture comme payée.
     */
    public function marquerCommePaye(string $id)
    {
        $facture = Facture::findOrFail($id);

        // Vérifier que tous les paiements sont bien effectués
        $totalPaiements = $facture->paiements()->sum('montant');

        if ($totalPaiements < $facture->montant_total) {
            return response()->json([
                'message' => 'Impossible de marquer cette facture comme payée car le montant total n\'est pas couvert par les paiements',
                'montant_total' => $facture->montant_total,
                'montant_paye' => $totalPaiements,
                'montant_restant' => $facture->montant_total - $totalPaiements
            ], 422);
        }

        $facture->update(['statut' => 'payée']);

        return response()->json([
            'message' => 'Facture marquée comme payée avec succès',
            'facture' => $facture
        ]);
    }

    /**
     * Obtenir les statistiques des factures.
     */
    public function getStatistiques(Request $request)
    {
        // Filtrage par année scolaire
        $anneeScolaireId = $request->annee_scolaire_id ?? null;

        // Si pas d'année spécifiée, prendre l'année active
        if (!$anneeScolaireId) {
            $anneeScolaireActive = AnneeScolaire::where('est_active', true)->first();

            if ($anneeScolaireActive) {
                $anneeScolaireId = $anneeScolaireActive->id;
            }
        }

        $query = Facture::query();

        // Filtrer par année scolaire si spécifiée
        if ($anneeScolaireId) {
            $query->whereHas('inscription', function ($q) use ($anneeScolaireId) {
                $q->where('annee_scolaire_id', $anneeScolaireId);
            });
        }

        // Nombre total de factures
        $totalFactures = $query->count();

        // Montant total des factures
        $montantTotalFactures = $query->sum('montant_total');

        // Répartition par statut
        $repartitionParStatut = $query->select('statut', DB::raw('count(*) as nombre'), DB::raw('sum(montant_total) as total'))
            ->groupBy('statut')
            ->get();

        return response()->json([
            'annee_scolaire_id' => $anneeScolaireId,
            'total_factures' => $totalFactures,
            'montant_total_factures' => $montantTotalFactures,
            'repartition_par_statut' => $repartitionParStatut
        ]);
    }
}
