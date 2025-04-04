<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UtilisateurRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Seuls les administrateurs peuvent gérer les utilisateurs
        $user = $this->user();
        return $user && $user->role === 'administrateur';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255'],
            'role' => 'required|in:administrateur',
        ];

        // Règles spécifiques selon le type de requête (création ou mise à jour)
        if ($this->isMethod('post')) {
            // Création d'utilisateur
            $rules['nom_utilisateur'] = 'required|string|unique:utilisateurs,nom_utilisateur';
            $rules['mot_de_passe'] = ['required', Password::min(8)->letters()->numbers()->symbols()];
            $rules['email'] = array_merge($rules['email'], ['unique:utilisateurs,email']);
        } else if ($this->isMethod('put') || $this->isMethod('patch')) {
            // Mise à jour d'utilisateur
            $userId = $this->route('utilisateur');
            $rules['nom_utilisateur'] = 'sometimes|required|string|unique:utilisateurs,nom_utilisateur,' . $userId;
            $rules['mot_de_passe'] = ['nullable', Password::min(8)->letters()->numbers()->symbols()];
            $rules['email'] = array_merge($rules['email'], ['unique:utilisateurs,email,' . $userId]);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom_utilisateur.required' => 'Le nom d\'utilisateur est obligatoire.',
            'nom_utilisateur.unique' => 'Ce nom d\'utilisateur est déjà utilisé.',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
            'mot_de_passe.min' => 'Le mot de passe doit comporter au moins :min caractères.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle sélectionné n\'est pas valide.',
        ];
    }
}
