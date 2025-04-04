<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inscription_id',
        'matiere_niveau_id',
        'session_id',
        'valeur',
        'appreciation',
        'date_saisie',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valeur' => 'decimal:2',
        'date_saisie' => 'date',
    ];

    /**
     * Obtenir l'inscription associée à cette note.
     */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    /**
     * Obtenir la matière de niveau associée à cette note.
     */
    public function matiereNiveau(): BelongsTo
    {
        return $this->belongsTo(MatiereNiveau::class);
    }

    /**
     * Obtenir la session associée à cette note.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Obtenir l'élève associé à cette note via l'inscription.
     */
    public function eleve()
    {
        return $this->inscription->eleve;
    }

    /**
     * Obtenir la matière associée à cette note via la matière de niveau.
     */
    public function matiere()
    {
        return $this->matiereNiveau->matiere;
    }
}
