<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EleveController extends Controller
{
    /**
     * Afficher une liste des élèves.
     */
    public function index(Request $request)
    {
        $query = Eleve::query();

        // Filtrage par sexe si spécifié
        if ($request->has('sexe') && in_array($request->sexe, ['M', 'F'])) {
            $query->where('sexe', $request->sexe);
        }

        // Recherche par nom/prénom/matricule si spécifié
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        // Trier par nom par défaut
        $eleves = $query->orderBy('nom')->orderBy('prenom')->paginate(15);

        return response()->json($eleves);
    }

    /**
     * Stocker un nouvel élève.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'matricule' => 'required|string|max:20|unique:eleves,matricule',
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'required|in:M,F',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'nom_parent' => 'required|string|max:255',
            'contact_parent' => 'required|string|max:20',
            'photo' => 'nullable|image|max:1024', // Max 1MB
            'date_inscription' => 'required|date',
        ]);

        // Traiter l'upload de la photo
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('photos/eleves', 'public');
            $validated['photo'] = $path;
        }

        $eleve = Eleve::create($validated);

        return response()->json([
            'message' => 'Élève créé avec succès',
            'eleve' => $eleve
        ], 201);
    }

    /**
     * Afficher un élève spécifique.
     */
    public function show(string $id)
    {
        $eleve = Eleve::findOrFail($id);

        // Récupérer les inscriptions avec classe et année scolaire
        $inscriptions = $eleve->inscriptions()->with(['classe.niveau', 'anneeScolaire'])->get();

        // Ajouter les inscriptions à l'élève
        $eleve->inscriptions_details = $inscriptions;

        return response()->json($eleve);
    }

    /**
     * Mettre à jour un élève spécifique.
     */
    public function update(Request $request, string $id)
    {
        $eleve = Eleve::findOrFail($id);

        $validated = $request->validate([
            'matricule' => 'sometimes|required|string|max:20|unique:eleves,matricule,' . $id,
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'date_naissance' => 'sometimes|required|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'sometimes|required|in:M,F',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'nom_parent' => 'sometimes|required|string|max:255',
            'contact_parent' => 'sometimes|required|string|max:20',
            'photo' => 'nullable|image|max:1024', // Max 1MB
        ]);

        // Traiter l'upload de la photo
        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($eleve->photo && Storage::disk('public')->exists($eleve->photo)) {
                Storage::disk('public')->delete($eleve->photo);
            }

            $path = $request->file('photo')->store('photos/eleves', 'public');
            $validated['photo'] = $path;
        }

        $eleve->update($validated);

        return response()->json([
            'message' => 'Élève mis à jour avec succès',
            'eleve' => $eleve
        ]);
    }

    /**
     * Supprimer un élève spécifique.
     */
    public function destroy(string $id)
    {
        $eleve = Eleve::findOrFail($id);

        // Vérifier si des inscriptions sont associées
        if ($eleve->inscriptions()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer cet élève car il a des inscriptions'
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
        $request->validate([
            'term' => 'required|string|min:2',
        ]);

        $term = $request->term;

        $eleves = Eleve::where('nom', 'like', "%{$term}%")
            ->orWhere('prenom', 'like', "%{$term}%")
            ->orWhere('matricule', 'like', "%{$term}%")
            ->limit(20)
            ->get();

        return response()->json($eleves);
    }

    /**
     * Obtenir la fiche d'information d'un élève.
     */
    public function getFiche(string $id)
    {
        $eleve = Eleve::findOrFail($id);

        // Récupérer l'année scolaire active
        $anneeScolaireActive = AnneeScolaire::where('est_active', true)->first();

        if (!$anneeScolaireActive) {
            return response()->json([
                'message' => 'Aucune année scolaire active trouvée'
            ], 404);
        }

        // Récupérer l'inscription active pour l'année scolaire en cours
        $inscriptionActive = $eleve->inscriptionActive($anneeScolaireActive->id);

        // Récupérer l'historique des inscriptions
        $historiqueInscriptions = $eleve->inscriptions()
            ->with(['classe.niveau', 'anneeScolaire'])
            ->orderBy('annee_scolaire_id', 'desc')
            ->get();

        return response()->json([
            'eleve' => $eleve,
            'inscription_active' => $inscriptionActive,
            'historique_inscriptions' => $historiqueInscriptions
        ]);
    }

    /**
     * Supprimer la photo d'un élève.
     */
    public function deletePhoto(string $id)
    {
        $eleve = Eleve::findOrFail($id);

        if (!$eleve->photo) {
            return response()->json([
                'message' => 'Cet élève n\'a pas de photo'
            ], 422);
        }

        // Supprimer la photo
        if (Storage::disk('public')->exists($eleve->photo)) {
            Storage::disk('public')->delete($eleve->photo);
        }

        $eleve->update(['photo' => null]);

        return response()->json([
            'message' => 'Photo supprimée avec succès'
        ]);
    }
}
