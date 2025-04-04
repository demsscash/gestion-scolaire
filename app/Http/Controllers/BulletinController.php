<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Inscription;
use App\Models\MatiereNiveau;
use App\Models\Note;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class BulletinController extends Controller
{
    /**
     * Afficher une liste de la ressource.
     */
    public function index()
    {
        $bulletins = Bulletin::with(['inscription.eleve', 'inscription.classe', 'session'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($bulletins);
    }

    /**
     * Stocker une nouvelle ressource dans le stockage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'session_id' => 'required|exists:sessions,id',
            'moyenne_generale' => 'required|numeric|min:0|max:20',
            'rang' => 'required|integer|min:1',
            'appreciation_generale' => 'nullable|string',
            'date_edition' => 'required|date',
            'decision' => 'required|in:passage,redoublement,en_attente',
        ]);

        // Vérifier si un bulletin existe déjà pour cette inscription et cette session
        $exists = Bulletin::where('inscription_id', $validated['inscription_id'])
            ->where('session_id', $validated['session_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Un bulletin existe déjà pour cet élève dans cette session'
            ], 422);
        }

        $bulletin = Bulletin::create($validated);

        return response()->json([
            'message' => 'Bulletin créé avec succès',
            'bulletin' => $bulletin
        ], 201);
    }

    /**
     * Afficher la ressource spécifiée.
     */
    public function show(string $id)
    {
        $bulletin = Bulletin::with([
            'inscription.eleve',
            'inscription.classe.niveau',
            'session.anneeScolaire'
        ])->findOrFail($id);

        // Récupérer les notes de l'élève pour cette session
        $notes = Note::with(['matiereNiveau.matiere'])
            ->where('inscription_id', $bulletin->inscription_id)
            ->where('session_id', $bulletin->session_id)
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

        return response()->json([
            'bulletin' => $bulletin,
            'notes' => $notes,
            'stats_matieres' => $statsMatiere
        ]);
    }

    /**
     * Mettre à jour la ressource spécifiée dans le stockage.
     */
    public function update(Request $request, string $id)
    {
        $bulletin = Bulletin::findOrFail($id);

        $validated = $request->validate([
            'moyenne_generale' => 'sometimes|required|numeric|min:0|max:20',
            'rang' => 'sometimes|required|integer|min:1',
            'appreciation_generale' => 'nullable|string',
            'date_edition' => 'sometimes|required|date',
            'decision' => 'sometimes|required|in:passage,redoublement,en_attente',
        ]);

        $bulletin->update($validated);

        return response()->json([
            'message' => 'Bulletin mis à jour avec succès',
            'bulletin' => $bulletin
        ]);
    }

    /**
     * Supprimer la ressource spécifiée du stockage.
     */
    public function destroy(string $id)
    {
        $bulletin = Bulletin::findOrFail($id);
        $bulletin->delete();

        return response()->json([
            'message' => 'Bulletin supprimé avec succès'
        ]);
    }

    /**
     * Obtenir les bulletins par inscription.
     */
    public function getByInscription(string $inscriptionId)
    {
        $bulletins = Bulletin::with(['session'])
            ->where('inscription_id', $inscriptionId)
            ->orderBy('session_id')
            ->get();

        return response()->json($bulletins);
    }

    /**
     * Obtenir les bulletins par classe et session.
     */
    public function getByClasseSession(string $classeId, string $sessionId)
    {
        $inscriptions = Inscription::where('classe_id', $classeId)->pluck('id');

        $bulletins = Bulletin::with(['inscription.eleve'])
            ->whereIn('inscription_id', $inscriptions)
            ->where('session_id', $sessionId)
            ->orderBy('rang')
            ->get();

        return response()->json($bulletins);
    }

    /**
     * Générer les bulletins pour tous les élèves d'une classe pour une session.
     */
    public function genererBulletinsClasse(string $classeId, string $sessionId)
    {
        $classe = Classe::with(['niveau'])->findOrFail($classeId);
        $session = Session::findOrFail($sessionId);

        // Obtenir toutes les inscriptions actives pour cette classe
        $inscriptions = Inscription::with(['eleve'])
            ->where('classe_id', $classeId)
            ->where('statut', 'actif')
            ->get();

        if ($inscriptions->isEmpty()) {
            return response()->json([
                'message' => 'Aucun élève inscrit dans cette classe'
            ], 404);
        }

        // Obtenir toutes les matières du niveau
        $matiereNiveaux = MatiereNiveau::with(['matiere'])
            ->where('niveau_id', $classe->niveau_id)
            ->get();

        if ($matiereNiveaux->isEmpty()) {
            return response()->json([
                'message' => 'Aucune matière configurée pour ce niveau'
            ], 404);
        }

        // Collecter toutes les notes pour calculer les moyennes
        $toutesLesNotes = [];
        $moyennesEleves = [];

        // Pour chaque élève, calculer la moyenne générale
        foreach ($inscriptions as $inscription) {
            $notes = Note::where('inscription_id', $inscription->id)
                ->where('session_id', $sessionId)
                ->get();

            $toutesLesNotes[$inscription->id] = $notes;

            // Calculer la moyenne générale pondérée
            $sommePonderee = 0;
            $sommeCoefficients = 0;

            foreach ($notes as $note) {
                $matiereNiveau = $matiereNiveaux->firstWhere('id', $note->matiere_niveau_id);
                if ($matiereNiveau) {
                    $sommePonderee += $note->valeur * $matiereNiveau->coefficient;
                    $sommeCoefficients += $matiereNiveau->coefficient;
                }
            }

            $moyenneGenerale = $sommeCoefficients > 0 ?
                round($sommePonderee / $sommeCoefficients, 2) : 0;

            $moyennesEleves[$inscription->id] = $moyenneGenerale;
        }

        // Trier les élèves par moyenne générale pour déterminer les rangs
        arsort($moyennesEleves);
        $rangs = array_flip(array_keys($moyennesEleves));

        // Créer ou mettre à jour les bulletins
        $bulletinsGeneres = [];

        DB::beginTransaction();
        try {
            foreach ($inscriptions as $inscription) {
                // Vérifier si un bulletin existe déjà
                $bulletin = Bulletin::where('inscription_id', $inscription->id)
                    ->where('session_id', $sessionId)
                    ->first();

                $donneesBulletin = [
                    'inscription_id' => $inscription->id,
                    'session_id' => $sessionId,
                    'moyenne_generale' => $moyennesEleves[$inscription->id],
                    'rang' => $rangs[$inscription->id] + 1, // +1 car les rangs commencent à 1
                    'date_edition' => now(),
                    'decision' => 'en_attente' // À définir manuellement plus tard
                ];

                if ($bulletin) {
                    $bulletin->update($donneesBulletin);
                } else {
                    $bulletin = Bulletin::create($donneesBulletin);
                }

                $bulletinsGeneres[] = $bulletin;
            }

            DB::commit();

            return response()->json([
                'message' => 'Bulletins générés avec succès',
                'bulletins' => $bulletinsGeneres
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la génération des bulletins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un PDF pour un bulletin.
     */
    public function generatePdf(string $id)
    {
        $bulletin = Bulletin::with([
            'inscription.eleve',
            'inscription.classe.niveau',
            'session.anneeScolaire'
        ])->findOrFail($id);

        // Récupérer les notes de l'élève pour cette session
        $notes = Note::with(['matiereNiveau.matiere'])
            ->where('inscription_id', $bulletin->inscription_id)
            ->where('session_id', $bulletin->session_id)
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

        $pdf = PDF::loadView('pdf.bulletin', [
            'bulletin' => $bulletin,
            'notes' => $notes,
            'stats_matieres' => $statsMatiere
        ]);

        $nomFichier = 'bulletin_' . $bulletin->inscription->eleve->nom . '_' .
            $bulletin->inscription->eleve->prenom . '_' .
            $bulletin->session->libelle . '.pdf';

        return $pdf->download($nomFichier);
    }

    /**
     * Générer un PDF pour tous les bulletins d'une classe.
     */
    public function generateBulletinsClassePdf(string $classeId, string $sessionId)
    {
        $classe = Classe::with(['niveau'])->findOrFail($classeId);
        $session = Session::with(['anneeScolaire'])->findOrFail($sessionId);

        $inscriptions = Inscription::where('classe_id', $classeId)
            ->where('statut', 'actif')
            ->pluck('id');

        $bulletins = Bulletin::with(['inscription.eleve'])
            ->whereIn('inscription_id', $inscriptions)
            ->where('session_id', $sessionId)
            ->orderBy('rang')
            ->get();

        if ($bulletins->isEmpty()) {
            return response()->json([
                'message' => 'Aucun bulletin trouvé pour cette classe et cette session'
            ], 404);
        }

        $pdf = PDF::loadView('pdf.bulletins_classe', [
            'bulletins' => $bulletins,
            'classe' => $classe,
            'session' => $session
        ]);

        $nomFichier = 'bulletins_' . $classe->nom . '_' . $session->libelle . '.pdf';

        return $pdf->download($nomFichier);
    }

    /**
     * Obtenir les statistiques pour une matière dans une classe.
     */
    private function getStatistiquesMatiereClasse(string $classeId, string $matiereNiveauId, string $sessionId)
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
