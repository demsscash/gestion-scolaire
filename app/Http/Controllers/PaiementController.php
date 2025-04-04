<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Inscription;
use App\Models\Facture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PaiementController extends Controller
{
    /**
     * Afficher une liste des paiements.
     */
    public function index(Request $request)
    {
        $query = Paiement::with(['inscription.eleve', 'inscription.classe']);

        // Filtrage par date
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('date_paiement', [$request->date_debut, $request->date_fin]);
        }

        // Filtrage par mode de paiement
        if ($request->has('mode_paiement') && in_array($request->mode_paiement, ['espèces', 'chèque', 'virement'])) {
            $query->where('mode_paiement', $request->mode_paiement);
        }

        $paiements = $query->orderBy('date_paiement', 'desc')->paginate(15);

        return response()->json($paiements);
    }

    /**
     * Stocker un nouveau paiement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'montant' => 'required|numeric|min:1',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|in:espèces,chèque,virement',
            'reference' => 'nullable|string|max:50',
            'mois_concerne' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'facture_id' => 'nullable|exists:factures,id',
        ]);

        // Vérifier que l'inscription existe et est active
        $inscription = Inscription::findOrFail($validated['inscription_id']);

        if ($inscription->statut !== 'actif') {
            return response()->json([
                'message' => 'Impossible d\'enregistrer un paiement pour un élève dont l\'inscription n\'est pas active'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Créer le paiement
            $paiement = Paiement::create($validated);

            // Si le paiement est associé à une facture, mettre à jour son statut
            if (isset($validated['facture_id'])) {
                $facture = Facture::findOrFail($validated['facture_id']);

                // Calculer le total des paiements pour cette facture
                $totalPaiements = Paiement::where('facture_id', $facture->id)->sum('montant') + $validated['montant'];

                // Mettre à jour le statut de la facture
                if ($totalPaiements >= $facture->montant_total) {
                    $facture->update(['statut' => 'payée']);
                } else {
                    $facture->update(['statut' => 'partiellement_payée']);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Paiement enregistré avec succès',
                'paiement' => $paiement
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l\'enregistrement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un paiement spécifique.
     */
    public function show(string $id)
    {
        $paiement = Paiement::with(['inscription.eleve', 'inscription.classe'])->findOrFail($id);
        return response()->json($paiement);
    }

    /**
     * Mettre à jour un paiement spécifique.
     */
    public function update(Request $request, string $id)
    {
        $paiement = Paiement::findOrFail($id);

        $validated = $request->validate([
            'montant' => 'sometimes|required|numeric|min:1',
            'date_paiement' => 'sometimes|required|date',
            'mode_paiement' => 'sometimes|required|in:espèces,chèque,virement',
            'reference' => 'nullable|string|max:50',
            'mois_concerne' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        // Empêcher la modification du montant si associé à une facture
        if (isset($validated['montant']) && $paiement->facture_id && $validated['montant'] != $paiement->montant) {
            return response()->json([
                'message' => 'Impossible de modifier le montant d\'un paiement associé à une facture'
            ], 422);
        }

        $paiement->update($validated);

        return response()->json([
            'message' => 'Paiement mis à jour avec succès',
            'paiement' => $paiement
        ]);
    }

    /**
     * Supprimer un paiement spécifique.
     */
    public function destroy(string $id)
    {
        $paiement = Paiement::findOrFail($id);

        // Empêcher la suppression si associé à une facture
        if ($paiement->facture_id) {
            return response()->json([
                'message' => 'Impossible de supprimer un paiement associé à une facture'
            ], 422);
        }

        $paiement->delete();

        return response()->json([
            'message' => 'Paiement supprimé avec succès'
        ]);
    }

    /**
     * Obtenir les paiements par inscription.
     */
    public function getByInscription(string $inscriptionId)
    {
        $paiements = Paiement::where('inscription_id', $inscriptionId)
            ->orderBy('date_paiement', 'desc')
            ->get();

        return response()->json($paiements);
    }

    /**
     * Générer un reçu de paiement au format PDF.
     */
    public function generateRecu(string $id)
    {
        $paiement = Paiement::with(['inscription.eleve', 'inscription.classe.niveau', 'inscription.anneeScolaire'])
            ->findOrFail($id);

        $pdf = PDF::loadView('pdf.recu_paiement', [
            'paiement' => $paiement
        ]);

        $nomFichier = 'recu_paiement_' . $paiement->id . '.pdf';

        return $pdf->download($nomFichier);
    }

    /**
     * Obtenir les statistiques des paiements.
     */
    public function getStatistiques(Request $request)
    {
        // Filtrage par période
        $dateDebut = $request->date_debut ?? date('Y-m-01'); // Premier jour du mois en cours
        $dateFin = $request->date_fin ?? date('Y-m-t'); // Dernier jour du mois en cours

        // Total des paiements sur la période
        $totalPaiements = Paiement::whereBetween('date_paiement', [$dateDebut, $dateFin])->sum('montant');

        // Nombre de paiements sur la période
        $nombrePaiements = Paiement::whereBetween('date_paiement', [$dateDebut, $dateFin])->count();

        // Répartition par mode de paiement
        $repartitionParMode = Paiement::whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->select('mode_paiement', DB::raw('count(*) as nombre'), DB::raw('sum(montant) as total'))
            ->groupBy('mode_paiement')
            ->get();

        return response()->json([
            'periode' => [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ],
            'total_paiements' => $totalPaiements,
            'nombre_paiements' => $nombrePaiements,
            'repartition_par_mode' => $repartitionParMode
        ]);
    }
}
