<?php

use App\Http\Controllers\AnneeScolaireController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\ClasseController;

use App\Http\Controllers\EleveController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\MatiereController;
use App\Http\Controllers\MatiereNiveauController;
use App\Http\Controllers\NiveauController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\EcoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes d'authentification
Route::post('/login', [UtilisateurController::class, 'login']);
Route::post('/logout', [UtilisateurController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/test', function () {
    return response()->json(['status' => 'ok']);
});

// Routes protégées par l'authentification
Route::middleware('auth:sanctum')->group(function () {
    // Informations de l'utilisateur connecté
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Routes pour l'école
    Route::apiResource('ecoles', EcoleController::class);

    // Routes pour les années scolaires
    Route::apiResource('annees-scolaires', AnneeScolaireController::class);
    Route::get('/annee-scolaire-active', [AnneeScolaireController::class, 'getActive']);

    // Routes pour les sessions
    Route::apiResource('sessions', SessionController::class);
    Route::get('/sessions/annee/{anneeScolaireId}', [SessionController::class, 'getByAnneeScolaire']);

    // Routes pour les niveaux
    Route::apiResource('niveaux', NiveauController::class);

    // Routes pour les classes
    Route::apiResource('classes', ClasseController::class);
    Route::get('/classes/annee/{anneeScolaireId}', [ClasseController::class, 'getByAnneeScolaire']);
    Route::get('/classes/niveau/{niveauId}', [ClasseController::class, 'getByNiveau']);

    // Routes pour les élèves
    Route::apiResource('eleves', EleveController::class);
    Route::get('/eleves/search', [EleveController::class, 'search']);

    // Routes pour les inscriptions
    Route::apiResource('inscriptions', InscriptionController::class);
    Route::get('/inscriptions/classe/{classeId}', [InscriptionController::class, 'getByClasse']);
    Route::get('/inscriptions/eleve/{eleveId}', [InscriptionController::class, 'getByEleve']);
    Route::get('/inscriptions/annee/{anneeScolaireId}', [InscriptionController::class, 'getByAnneeScolaire']);

    // Routes pour les matières
    Route::apiResource('matieres', MatiereController::class);

    // Routes pour les matières par niveau
    Route::apiResource('matiere-niveaux', MatiereNiveauController::class);
    Route::get('/matiere-niveaux/niveau/{niveauId}', [MatiereNiveauController::class, 'getByNiveau']);

    // Routes pour les notes
    Route::apiResource('notes', NoteController::class);
    Route::get('/notes/inscription/{inscriptionId}', [NoteController::class, 'getByInscription']);
    Route::get('/notes/classe/{classeId}/matiere/{matiereId}/session/{sessionId}', [NoteController::class, 'getByClasseMatiereSession']);
    Route::post('/notes/bulk', [NoteController::class, 'storeBulk']);

    // Routes pour les bulletins
    Route::apiResource('bulletins', BulletinController::class);
    Route::get('/bulletins/inscription/{inscriptionId}', [BulletinController::class, 'getByInscription']);
    Route::get('/bulletins/classe/{classeId}/session/{sessionId}', [BulletinController::class, 'getByClasseSession']);
    Route::post('/bulletins/generer/classe/{classeId}/session/{sessionId}', [BulletinController::class, 'genererBulletinsClasse']);
    Route::get('/bulletins/{id}/pdf', [BulletinController::class, 'generatePdf']);
    Route::get('/bulletins/classe/{classeId}/session/{sessionId}/pdf', [BulletinController::class, 'generateBulletinsClassePdf']);

    // Routes pour les paiements
    Route::apiResource('paiements', PaiementController::class);
    Route::get('/paiements/inscription/{inscriptionId}', [PaiementController::class, 'getByInscription']);
    Route::get('/paiements/recu/{id}', [PaiementController::class, 'generateRecu']);

    // Routes pour les factures
    Route::apiResource('factures', FactureController::class);
    Route::get('/factures/inscription/{inscriptionId}', [FactureController::class, 'getByInscription']);
    Route::get('/factures/{id}/pdf', [FactureController::class, 'generatePdf']);
});

Route::get('/test', function () {
    return response()->json(['status' => 'ok']);
});
