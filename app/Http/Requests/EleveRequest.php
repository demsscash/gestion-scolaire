<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EleveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Tous les utilisateurs authentifiés peuvent gérer les élèves
        return true;
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
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'required|in:M,F',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'nom_parent' => 'required|string|max:255',
            'contact_parent' => 'required|string|max:20',
            'photo' => 'nullable|image|max:2048', // Max 2MB
            'date_inscription' => 'required|date',
        ];

        // Règles spécifiques selon le type de requête (création ou mise à jour)
        if ($this->isMethod('post')) {
            // Création d'élève
            $rules['matricule'] = 'required|string|max:20|unique:eleves,matricule';
        } else if ($this->isMethod('put') || $this->isMethod('patch')) {
            // Mise à jour d'élève
            $eleveId = $this->route('eleve');
            $rules['matricule'] = 'sometimes|required|string|max:20|unique:eleves,matricule,' . $eleveId;
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
            'matricule.required' => 'Le matricule est obligatoire.',
            'matricule.unique' => 'Ce matricule est déjà utilisé par un autre élève.',
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'sexe.required' => 'Le sexe est obligatoire.',
            'sexe.in' => 'Le sexe doit être M (masculin) ou F (féminin).',
            'nom_parent.required' => 'Le nom du parent/tuteur est obligatoire.',
            'contact_parent.required' => 'Le contact du parent/tuteur est obligatoire.',
            'date_inscription.required' => 'La date d\'inscription est obligatoire.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.max' => 'La taille de l\'image ne doit pas dépasser 2 Mo.',
        ];
    }
}
