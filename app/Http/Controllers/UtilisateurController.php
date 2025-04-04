<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use App\Http\Requests\UtilisateurRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;



class UtilisateurController extends Controller
{
    /**
     * Connecter un utilisateur et générer un token Sanctum.
     */
    public function login(Request $request)
    {
        $request->validate([
            'nom_utilisateur' => 'required|string',
            'mot_de_passe' => 'required|string',
        ]);

        $utilisateur = Utilisateur::where('nom_utilisateur', $request->nom_utilisateur)->first();

        if (!$utilisateur || !Hash::check($request->mot_de_passe, $utilisateur->mot_de_passe)) {
            throw ValidationException::withMessages([
                'nom_utilisateur' => ['Les informations d\'identification fournies sont incorrectes.'],
            ]);
        }

        // Mettre à jour la date de dernière connexion
        $utilisateur->update([
            'dernier_login' => now(),
        ]);

        // Créer un token avec le nom de l'appareil
        $token = $utilisateur->createToken($request->device_name ?? 'api_token')->plainTextToken;

        return response()->json([
            'utilisateur' => $utilisateur,
            'token' => $token,
        ]);
    }

    /**
     * Déconnecter l'utilisateur (révoquer le token).
     */
    public function logout(Request $request)
    {
        // Supprimer le token actuel
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Afficher une liste de la ressource.
     */
    public function index()
    {
        $utilisateurs = Utilisateur::orderBy('nom')->paginate(10);

        return response()->json($utilisateurs);
    }

    /**
     * Stocker une nouvelle ressource dans le stockage.
     */
    public function store(UtilisateurRequest $request)
    {
        $validated = $request->validated();

        // Hacher le mot de passe
        $validated['mot_de_passe'] = Hash::make($validated['mot_de_passe']);

        $utilisateur = Utilisateur::create($validated);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'utilisateur' => $utilisateur,
        ], 201);
    }

    /**
     * Afficher la ressource spécifiée.
     */
    public function show(string $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        return response()->json($utilisateur);
    }

    /**
     * Mettre à jour la ressource spécifiée dans le stockage.
     */
    public function update(UtilisateurRequest $request, string $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        $validated = $request->validated();

        // Hacher le mot de passe si fourni
        if (isset($validated['mot_de_passe'])) {
            $validated['mot_de_passe'] = Hash::make($validated['mot_de_passe']);
        }

        $utilisateur->update($validated);

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'utilisateur' => $utilisateur,
        ]);
    }

    /**
     * Supprimer la ressource spécifiée du stockage.
     */
    public function destroy(string $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        // Vérifier qu'il reste au moins un administrateur
        $adminCount = Utilisateur::where('role', 'administrateur')->count();

        if ($adminCount <= 1 && $utilisateur->role === 'administrateur') {
            return response()->json([
                'message' => 'Impossible de supprimer le dernier administrateur du système'
            ], 422);
        }

        $utilisateur->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }

    /**
     * Modifier le mot de passe de l'utilisateur connecté.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Vérifier le mot de passe actuel
        if (!Hash::check($request->current_password, $user->mot_de_passe)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update([
            'mot_de_passe' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès',
        ]);
    }

    /**
     * Obtenir le profil de l'utilisateur connecté.
     */
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Révoquer tous les tokens d'un utilisateur (déconnexion de tous les appareils).
     */
    public function revokeAllTokens(string $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        // Supprimer tous les tokens de l'utilisateur
        $utilisateur->tokens()->delete();

        return response()->json([
            'message' => 'Tous les tokens de l\'utilisateur ont été révoqués'
        ]);
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur.
     */
    public function resetPassword(string $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        // Générer un mot de passe aléatoire
        $newPassword = Str::random(10);

        $utilisateur->update([
            'mot_de_passe' => Hash::make($newPassword)
        ]);

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès',
            'nouveau_mot_de_passe' => $newPassword
        ]);
    }
}
