<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matiere extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'libelle',
        'description',
    ];

    /**
     * Obtenir les niveaux associés à cette matière.
     */
    public function niveaux(): BelongsToMany
    {
        return $this->belongsToMany(Niveau::class, 'matiere_niveaux')
            ->withPivot('coefficient')
            ->withTimestamps();
    }

    /**
     * Obtenir les configurations de cette matière par niveau.
     */
    public function matiereNiveaux(): HasMany
    {
        return $this->hasMany(MatiereNiveau::class);
    }
}
