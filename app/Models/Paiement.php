<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inscription_id',
        'facture_id',
        'montant',
        'date_paiement',
        'mode_paiement',
        'reference',
        'mois_concerne',
        'description',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
    ];

    /**
     * Obtenir l'inscription associée à ce paiement.
     */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    /**
     * Obtenir la facture associée à ce paiement.
     */
    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    /**
     * Obtenir l'élève associé à ce paiement via l'inscription.
     */
    public function eleve()
    {
        return $this->inscription->eleve;
    }

    /**
     * Obtenir la classe associée à ce paiement via l'inscription.
     */
    public function classe()
    {
        return $this->inscription->classe;
    }
}
