<?php

namespace App\Utils;

use App\Models\Facture;
use App\Models\Bulletin;
use App\Models\Eleve;
use Carbon\Carbon;

class ReferenceGenerator
{
    /**
     * Générer un numéro de facture unique.
     *
     * @return string Numéro de facture au format FAC-AAAAMMJJ-XXX
     */
    public static function generateFactureNumber(): string
    {
        $prefix = 'FAC';
        $date = Carbon::now()->format('Ymd');

        // Chercher la dernière facture créée aujourd'hui
        $lastFacture = Facture::where('numero', 'like', "{$prefix}-{$date}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastFacture) {
            // Extraire le numéro de séquence de la dernière facture
            $parts = explode('-', $lastFacture->numero);
            $lastSequence = (int) end($parts);
            $sequence = $lastSequence + 1;
        }

        // Formater le numéro de séquence sur 3 chiffres
        $sequenceFormatted = str_pad($sequence, 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$sequenceFormatted}";
    }

    /**
     * Générer un matricule élève unique.
     *
     * @param string $nom Nom de l'élève
     * @param string $prenom Prénom de l'élève
     * @param Carbon|string $dateNaissance Date de naissance de l'élève
     * @return string Matricule au format AA-XXXXX (année d'inscription - numéro séquentiel)
     */
    public static function generateMatricule(string $nom, string $prenom, $dateNaissance): string
    {
        $academicYear = self::getCurrentAcademicYear();
        $yearPrefix = substr($academicYear, 2, 2); // Exemple: '23' pour '2023-2024'

        // Initialiser un code basé sur le nom et prénom
        $initials = strtoupper(substr($nom, 0, 1) . substr($prenom, 0, 1));

        // Convertir la date de naissance en objet Carbon si nécessaire
        if (!$dateNaissance instanceof Carbon) {
            $dateNaissance = Carbon::parse($dateNaissance);
        }

        // Partie numérique basée sur la date de naissance
        $birthCode = $dateNaissance->format('dmy');

        // Chercher le dernier matricule avec ce préfixe
        $lastMatricule = Eleve::where('matricule', 'like', "{$yearPrefix}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastMatricule) {
            // Extraire le numéro de séquence du dernier matricule
            $parts = explode('-', $lastMatricule->matricule);
            if (count($parts) >= 2) {
                $lastSequence = (int) $parts[1];
                $sequence = $lastSequence + 1;
            }
        }

        // Formater le numéro de séquence sur 5 chiffres
        $sequenceFormatted = str_pad($sequence, 5, '0', STR_PAD_LEFT);

        return "{$yearPrefix}-{$sequenceFormatted}";
    }

    /**
     * Générer un numéro de reçu de paiement.
     *
     * @param int $paiementId ID du paiement
     * @param string $modePaiement Mode de paiement
     * @return string Numéro de reçu au format RECU-AAAAMMJJ-XXX
     */
    public static function generateRecuNumber(int $paiementId, string $modePaiement): string
    {
        $prefix = 'RECU';
        $date = Carbon::now()->format('Ymd');
        $modeSuffix = substr(strtoupper($modePaiement), 0, 1); // E, C ou V

        return "{$prefix}-{$date}-{$paiementId}-{$modeSuffix}";
    }

    /**
     * Générer une référence pour un bulletin.
     *
     * @param Bulletin $bulletin
     * @return string Référence de bulletin au format BUL-CLASSE-SESSION-ID
     */
    public static function generateBulletinReference(Bulletin $bulletin): string
    {
        $bulletin->load(['inscription.classe', 'session']);

        $prefix = 'BUL';
        $classeCode = $bulletin->inscription->classe->nom;
        $sessionCode = strtoupper(substr($bulletin->session->libelle, 0, 3));

        return "{$prefix}-{$classeCode}-{$sessionCode}-{$bulletin->id}";
    }

    /**
     * Obtenir l'année académique courante au format AAAA-AAAA.
     *
     * @return string
     */
    public static function getCurrentAcademicYear(): string
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;

        // Si on est entre janvier et août, on est dans l'année académique qui a commencé l'année précédente
        if ($month >= 1 && $month <= 8) {
            return ($year - 1) . '-' . $year;
        }

        // Sinon on est dans l'année académique qui commence cette année
        return $year . '-' . ($year + 1);
    }
}
