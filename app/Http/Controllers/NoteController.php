<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Inscription;
use App\Models\MatiereNiveau;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    /**
     * Afficher une liste de la ressource.
     */
    public function index()
    {
        $notes = Note::with(['inscription.eleve', 'matiereNiveau.matiere', 'session'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($notes);
    }

    /**
     * Stocker une nouvelle ressource dans le stockage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'matiere_niveau_id' => 'required|exists:matiere_niveaux,id',
            'session_id' => 'required|exists:sessions,id',
            'valeur' => 'required|numeric|min:0|max:20',
            'appreciation' => 'nullable|string',
            'date_saisie' => 'required|date',
        ]);

        // Vérifier si une note existe déjà pour cet élève dans cette matière et session
        $exists = Note::where('inscription_id', $validated['inscription_id'])
            ->where('matiere_niveau_id', $validated['matiere_niveau_id'])
            ->where('session_id', $validated['session_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Une note existe déjà pour cet élève dans cette matière et session'
            ], 422);
        }

        $note = Note::create($validated);

        return response()->json([
            'message' => 'Note créée avec succès',
            'note' => $note
        ], 201);
    }

    /**
     * Afficher la ressource spécifiée.
     */
    public function show(string $id)
    {
        $note = Note::with(['inscription.eleve', 'matiereNiveau.matiere', 'session'])
            ->findOrFail($id);

        return response()->json($note);
    }

    /**
     * Mettre à jour la ressource spécifiée dans le stockage.
     */
    public function update(Request $request, string $id)
    {
        $note = Note::findOrFail($id);

        $validated = $request->validate([
            'valeur' => 'required|numeric|min:0|max:20',
            'appreciation' => 'nullable|string',
            'date_saisie' => 'sometimes|required|date',
        ]);

        $note->update($validated);

        return response()->json([
            'message' => 'Note mise à jour avec succès',
            'note' => $note
        ]);
    }

    /**
     * Supprimer la ressource spécifiée du stockage.
     */
    public function destroy(string $id)
    {
        $note = Note::findOrFail($id);
        $note->delete();

        return response()->json([
            'message' => 'Note supprimée avec succès'
        ]);
    }

    /**
     * Obtenir les notes par inscription.
     */
    public function getByInscription(string $inscriptionId)
    {
        $notes = Note::with(['matiereNiveau.matiere', 'session'])
            ->where('inscription_id', $inscriptionId)
            ->orderBy('session_id')
            ->get();

        return response()->json($notes);
    }

    /**
     * Obtenir les notes par classe, matière et session.
     */
    public function getByClasseMatiereSession(string $classeId, string $matiereId, string $sessionId)
    {
        // Trouver le niveau associé à la classe
        $classe = Classe::with('niveau')->findOrFail($classeId);

        // Trouver la configuration matière-niveau
        $matiereNiveau = MatiereNiveau::where('matiere_id', $matiereId)
            ->where('niveau_id', $classe->niveau_id)
            ->firstOrFail();

        // Récupérer les inscriptions de la classe
        $inscriptions = Inscription::where('classe_id', $classeId)
            ->where('statut', 'actif')
            ->with('eleve')
            ->get();

        // Récupérer les notes existantes
        $notes = Note::where('matiere_niveau_id', $matiereNiveau->id)
            ->where('session_id', $sessionId)
            ->whereIn('inscription_id', $inscriptions->pluck('id'))
            ->get()
            ->keyBy('inscription_id');

        // Préparer le résultat avec tous les élèves, même ceux sans note
        $result = [];
        foreach ($inscriptions as $inscription) {
            $note = $notes->get($inscription->id);

            $result[] = [
                'inscription_id' => $inscription->id,
                'eleve' => [
                    'id' => $inscription->eleve->id,
                    'matricule' => $inscription->eleve->matricule,
                    'nom' => $inscription->eleve->nom,
                    'prenom' => $inscription->eleve->prenom,
                ],
                'note' => $note ? [
                    'id' => $note->id,
                    'valeur' => $note->valeur,
                    'appreciation' => $note->appreciation,
                ] : null
            ];
        }

        return response()->json([
            'classe' => $classe,
            'matiere_niveau' => $matiereNiveau,
            'eleves_notes' => $result
        ]);
    }

    /**
     * Stocker plusieurs notes en une seule requête.
     */
    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'notes' => 'required|array',
            'notes.*.inscription_id' => 'required|exists:inscriptions,id',
            'notes.*.matiere_niveau_id' => 'required|exists:matiere_niveaux,id',
            'notes.*.session_id' => 'required|exists:sessions,id',
            'notes.*.valeur' => 'required|numeric|min:0|max:20',
            'notes.*.appreciation' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $notesCreated = [];
            $notesUpdated = [];

            foreach ($validated['notes'] as $noteData) {
                // Ajouter la date de saisie si non fournie
                if (!isset($noteData['date_saisie'])) {
                    $noteData['date_saisie'] = now();
                }

                // Vérifier si la note existe déjà
                $note = Note::where('inscription_id', $noteData['inscription_id'])
                    ->where('matiere_niveau_id', $noteData['matiere_niveau_id'])
                    ->where('session_id', $noteData['session_id'])
                    ->first();

                if ($note) {
                    $note->update($noteData);
                    $notesUpdated[] = $note;
                } else {
                    $newNote = Note::create($noteData);
                    $notesCreated[] = $newNote;
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Notes enregistrées avec succès',
                'notes_created' => count($notesCreated),
                'notes_updated' => count($notesUpdated)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l\'enregistrement des notes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
