<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'niveau_id',
        'annee_scolaire_id',
        'nom',
        'capacite',
        'titulaire',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacite' => 'integer',
    ];

    /**
     * Obtenir le niveau associé à cette classe.
     */
    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    /**
     * Obtenir l'année scolaire associée à cette classe.
     */
    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    /**
     * Obtenir les inscriptions pour cette classe.
     */
    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    /**
     * Obtenir les élèves inscrits dans cette classe.
     */
    public function eleves()
    {
        return $this->belongsToMany(Eleve::class, 'inscriptions')
            ->withPivot('date_inscription', 'statut')
            ->withTimestamps();
    }
}
