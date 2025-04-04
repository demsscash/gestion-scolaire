<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facture extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inscription_id',
        'numero',
        'date_emission',
        'montant_total',
        'statut',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant_total' => 'decimal:2',
        'date_emission' => 'date',
    ];

    /**
     * Obtenir l'inscription associée à cette facture.
     */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    /**
     * Obtenir les paiements associés à cette facture.
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Obtenir l'élève associé à cette facture via l'inscription.
     */
    public function eleve()
    {
        return $this->inscription->eleve;
    }

    /**
     * Obtenir la classe associée à cette facture via l'inscription.
     */
    public function classe()
    {
        return $this->inscription->classe;
    }
}
