<?php

namespace Database\Seeders;

use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UtilisateurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Utilisateur::create([
            'nom_utilisateur' => 'admin',
            'mot_de_passe' => Hash::make('password'),
            'nom' => 'Administrateur',
            'prenom' => 'Système',
            'email' => 'admin@ecole.com',
            'role' => 'administrateur',
        ]);
    }
}
