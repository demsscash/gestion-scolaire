<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eleve extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'adresse',
        'telephone',
        'email',
        'nom_parent',
        'contact_parent',
        'photo',
        'date_inscription',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_naissance' => 'date',
        'date_inscription' => 'date',
    ];

    /**
     * Obtenir le nom complet de l'élève.
     *
     * @return string
     */
    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    /**
     * Obtenir les inscriptions de cet élève.
     */
    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    /**
     * Obtenir les classes de cet élève (via les inscriptions).
     */
    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'inscriptions')
            ->withPivot('date_inscription', 'statut')
            ->withTimestamps();
    }

    /**
     * Obtenir l'inscription active pour l'année scolaire en cours.
     *
     * @param int $anneeScolaireId
     * @return Inscription|null
     */
    public function inscriptionActive($anneeScolaireId)
    {
        return $this->inscriptions()
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'actif')
            ->first();
    }
}
