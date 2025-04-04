<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;


Route::get('/', function () {
    return view('welcome');
});

// Route pour la documentation API
Route::get('/api/documentation', function () {
    return view('api.docs');
})->name('api.documentation');

// Route pour télécharger le fichier de définition OpenAPI
Route::get('/api-docs.json', function () {
    $openApiPath = resource_path('openapi/api-docs.json');

    // Si le fichier json n'existe pas, convertir le yaml en json
    if (!file_exists($openApiPath)) {
        $yamlPath = resource_path('openapi/api-docs.yaml');
        if (file_exists($yamlPath)) {
            $yaml = file_get_contents($yamlPath);
            $json = json_encode(Yaml::parse($yaml), JSON_PRETTY_PRINT);

            // Créer le répertoire s'il n'existe pas
            if (!file_exists(dirname($openApiPath))) {
                mkdir(dirname($openApiPath), 0755, true);
            }

            // Enregistrer le json
            file_put_contents($openApiPath, $json);
        } else {
            return response()->json(['error' => 'API documentation file not found'], 404);
        }
    }

    return response()->file($openApiPath);
})->name('api.docs.json');
