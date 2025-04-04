<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EleveController extends Controller
{
    /**
     * Afficher une liste de la ressource.
     */
    public function index()
    {
        $eleves = Eleve::orderBy('nom')->orderBy('prenom')->paginate(15);

        return response()->json($eleves);
    }

    /**
     * Stocker une nouvelle ressource dans le stockage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'matricule' => 'required|string|unique:eleves,matricule',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'sexe' => 'required|in:M,F',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'nom_parent' => 'required|string|max:100',
            'contact_parent' => 'required|string|max:100',
            'photo' => 'nullable|image|max:2048', // 2MB max
            'date_inscription' => 'required|date',
        ]);

        // Traitement de la photo
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos_eleves', 'public');
            $validated['photo'] = $photoPath;
        }

        $eleve = Eleve::create($validated);

        return response()->json([
            'message' => 'Élève créé avec succès',
            'eleve' => $eleve
        ], 201);
    }

    /**
     * Afficher la ressource spécifiée.
     */
    public function show(string $id)
    {
        $eleve = Eleve::with(['inscriptions.classe.niveau', 'inscriptions.anneeScolaire'])
            ->findOrFail($id);

        return response()->json($eleve);
    }

    /**
     * Mettre à jour la ressource spécifiée dans le stockage.
     */
    public function update(Request $request, string $id)
    {
        $eleve = Eleve::findOrFail($id);

        $validated = $request->validate([
            'matricule' => 'sometimes|required|string|unique:eleves,matricule,' . $id,
            'nom' => 'sometimes|required|string|max:100',
            'prenom' => 'sometimes|required|string|max:100',
            'date_naissance' => 'sometimes|required|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'sexe' => 'sometimes|required|in:M,F',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'nom_parent' => 'sometimes|required|string|max:100',
            'contact_parent' => 'sometimes|required|string|max:100',
            'photo' => 'nullable|image|max:2048', // 2MB max
            'date_inscription' => 'sometimes|required|date',
        ]);

        // Traitement de la photo
        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($eleve->photo && Storage::disk('public')->exists($eleve->photo)) {
                Storage::disk('public')->delete($eleve->photo);
            }

            $photoPath = $request->file('photo')->store('photos_eleves', 'public');
            $validated['photo'] = $photoPath;
        }

        $eleve->update($validated);

        return response()->json([
            'message' => 'Élève mis à jour avec succès',
            'eleve' => $eleve
        ]);
    }

    /**
     * Supprimer la ressource spécifiée du stockage.
     */
    public function destroy(string $id)
    {
        $eleve = Eleve::findOrFail($id);

        // Vérifier si l'élève a des inscriptions
        if ($eleve->inscriptions()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer cet élève car il a des inscriptions associées'
            ], 422);
        }

        // Supprimer la photo si elle existe
        if ($eleve->photo && Storage::disk('public')->exists($eleve->photo)) {
            Storage::disk('public')->delete($eleve->photo);
        }

        $eleve->delete();

        return response()->json([
            'message' => 'Élève supprimé avec succès'
        ]);
    }

    /**
     * Rechercher des élèves.
     */
    public function search(Request $request)
    {
        $query = Eleve::query();

        // Recherche par matricule
        if ($request->has('matricule')) {
            $query->where('matricule', 'like', '%' . $request->matricule . '%');
        }

        // Recherche par nom
        if ($request->has('nom')) {
            $query->where('nom', 'like', '%' . $request->nom . '%');
        }

        // Recherche par prénom
        if ($request->has('prenom')) {
            $query->where('prenom', 'like', '%' . $request->prenom . '%');
        }

        // Recherche par date de naissance
        if ($request->has('date_naissance')) {
            $query->whereDate('date_naissance', $request->date_naissance);
        }

        $eleves = $query->orderBy('nom')->orderBy('prenom')->paginate(15);

        return response()->json($eleves);
    }

    /**
     * Récupérer la photo d'un élève.
     */
    public function getPhoto(string $id)
    {
        $eleve = Eleve::findOrFail($id);

        if (!$eleve->photo || !Storage::disk('public')->exists($eleve->photo)) {
            return response()->json([
                'message' => 'Aucune photo trouvée pour cet élève'
            ], 404);
        }

        return response()->file(Storage::disk('public')->path($eleve->photo));
    }

    /**
     * Obtenir tous les élèves d'une classe spécifique.
     */
    public function getByClasse(string $classeId)
    {
        $eleves = Eleve::whereHas('inscriptions', function ($query) use ($classeId) {
            $query->where('classe_id', $classeId)
                ->where('statut', 'actif');
        })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return response()->json($eleves);
    }

    /**
     * Obtenir tous les élèves inscrits pour une année scolaire spécifique.
     */
    public function getByAnneeScolaire(string $anneeScolaireId)
    {
        $eleves = Eleve::whereHas('inscriptions', function ($query) use ($anneeScolaireId) {
            $query->where('annee_scolaire_id', $anneeScolaireId);
        })
            ->with(['inscriptions' => function ($query) use ($anneeScolaireId) {
                $query->where('annee_scolaire_id', $anneeScolaireId)
                    ->with('classe');
            }])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return response()->json($eleves);
    }
}
