<?php

namespace App\Services;

use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Facture;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Session;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Note;



class PdfService
{
    /**
     * Générer un bulletin de notes individuel au format PDF.
     *
     * @param Bulletin $bulletin
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateBulletin(Bulletin $bulletin)
    {
        // Charger le bulletin avec toutes les relations nécessaires
        $bulletin->load([
            'inscription.eleve',
            'inscription.classe.niveau',
            'session.anneeScolaire'
        ]);

        // Récupérer les notes et statistiques
        $notes = $bulletin->inscription->notes()
            ->where('session_id', $bulletin->session_id)
            ->with('matiereNiveau.matiere')
            ->get();

        // Calculer les statistiques de classe pour chaque matière
        $statsMatiere = [];
        foreach ($notes as $note) {
            $stats = $this->getStatistiquesMatiereClasse(
                $bulletin->inscription->classe_id,
                $note->matiereNiveau->id,
                $bulletin->session_id
            );

            $statsMatiere[$note->matiereNiveau->id] = $stats;
        }

        return Pdf::loadView('pdf.bulletin', [
            'bulletin' => $bulletin,
            'notes' => $notes,
            'stats_matieres' => $statsMatiere
        ]);
    }

    /**
     * Générer un PDF contenant tous les bulletins d'une classe.
     *
     * @param Classe $classe
     * @param Session $session
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateBulletinsClasse(Classe $classe, Session $session)
    {
        $classe->load('niveau');
        $session->load('anneeScolaire');

        $inscriptions = Inscription::where('classe_id', $classe->id)
            ->where('statut', 'actif')
            ->pluck('id');

        $bulletins = Bulletin::with(['inscription.eleve'])
            ->whereIn('inscription_id', $inscriptions)
            ->where('session_id', $session->id)
            ->orderBy('rang')
            ->get();

        return Pdf::loadView('pdf.bulletins_classe', [
            'bulletins' => $bulletins,
            'classe' => $classe,
            'session' => $session
        ]);
    }

    /**
     * Générer une attestation d'inscription au format PDF.
     *
     * @param Inscription $inscription
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateAttestationInscription(Inscription $inscription)
    {
        $inscription->load(['eleve', 'classe.niveau', 'anneeScolaire']);

        return Pdf::loadView('pdf.attestation_inscription', [
            'inscription' => $inscription
        ]);
    }

    /**
     * Générer un reçu de paiement au format PDF.
     *
     * @param Paiement $paiement
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateRecuPaiement(Paiement $paiement)
    {
        $paiement->load(['inscription.eleve', 'inscription.classe.niveau', 'inscription.anneeScolaire']);

        return Pdf::loadView('pdf.recu_paiement', [
            'paiement' => $paiement
        ]);
    }

    /**
     * Générer une facture au format PDF.
     *
     * @param Facture $facture
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateFacture(Facture $facture)
    {
        $facture->load([
            'inscription.eleve',
            'inscription.classe.niveau',
            'inscription.anneeScolaire',
            'paiements'
        ]);

        // Calculer le montant payé et restant
        $totalPaiements = $facture->paiements()->sum('montant');
        $facture->montant_paye = $totalPaiements;
        $facture->montant_restant = $facture->montant_total - $totalPaiements;

        return Pdf::loadView('pdf.facture', [
            'facture' => $facture
        ]);
    }

    /**
     * Obtenir les statistiques pour une matière dans une classe.
     *
     * @param int $classeId
     * @param int $matiereNiveauId
     * @param int $sessionId
     * @return array
     */
    private function getStatistiquesMatiereClasse($classeId, $matiereNiveauId, $sessionId)
    {
        $inscriptions = Inscription::where('classe_id', $classeId)
            ->where('statut', 'actif')
            ->pluck('id');

        $notes = Note::where('matiere_niveau_id', $matiereNiveauId)
            ->where('session_id', $sessionId)
            ->whereIn('inscription_id', $inscriptions)
            ->pluck('valeur');

        if ($notes->isEmpty()) {
            return [
                'moyenne_classe' => 0,
                'note_min' => 0,
                'note_max' => 0
            ];
        }

        return [
            'moyenne_classe' => round($notes->avg(), 2),
            'note_min' => $notes->min(),
            'note_max' => $notes->max()
        ];
    }
}
