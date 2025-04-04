<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bulletin extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inscription_id',
        'session_id',
        'moyenne_generale',
        'rang',
        'appreciation_generale',
        'date_edition',
        'decision',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'moyenne_generale' => 'decimal:2',
        'rang' => 'integer',
        'date_edition' => 'date',
    ];

    /**
     * Obtenir l'inscription associée à ce bulletin.
     */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    /**
     * Obtenir la session associée à ce bulletin.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Obtenir l'élève associé à ce bulletin via l'inscription.
     */
    public function eleve()
    {
        return $this->inscription->eleve;
    }

    /**
     * Obtenir la classe associée à ce bulletin via l'inscription.
     */
    public function classe()
    {
        return $this->inscription->classe;
    }

    /**
     * Obtenir les notes de l'élève pour cette session.
     */
    public function notes()
    {
        return Note::where('inscription_id', $this->inscription_id)
            ->where('session_id', $this->session_id)
            ->get();
    }
}
