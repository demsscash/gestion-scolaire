<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * Stocker un fichier téléchargé
     *
     * @param UploadedFile $file Le fichier téléchargé
     * @param string $directory Le répertoire de destination
     * @param string|null $filename Nom de fichier personnalisé (optionnel)
     * @param string $disk Le disque de stockage (par défaut: 'public')
     * @return string|false Chemin du fichier stocké ou false en cas d'échec
     */
    public function store(UploadedFile $file, string $directory, ?string $filename = null, string $disk = 'public')
    {
        if (!$file->isValid()) {
            return false;
        }

        // Si aucun nom de fichier n'est fourni, en générer un unique
        if (!$filename) {
            $extension = $file->getClientOriginalExtension();
            $filename = Str::random(40) . '.' . $extension;
        }

        // Assurer que le répertoire se termine par un slash
        $directory = rtrim($directory, '/') . '/';

        // Stocker le fichier
        $path = $file->storeAs($directory, $filename, $disk);

        return $path;
    }

    /**
     * Supprimer un fichier
     *
     * @param string $path Chemin du fichier à supprimer
     * @param string $disk Le disque de stockage (par défaut: 'public')
     * @return bool Succès de la suppression
     */
    public function delete(string $path, string $disk = 'public'): bool
    {
        if (!$path || !Storage::disk($disk)->exists($path)) {
            return false;
        }

        return Storage::disk($disk)->delete($path);
    }

    /**
     * Obtenir l'URL d'un fichier
     *
     * @param string $path Chemin du fichier
     * @param string $disk Le disque de stockage (par défaut: 'public')
     * @return string|null URL du fichier ou null si le fichier n'existe pas
     */
    public function url(string $path, string $disk = 'public'): ?string
    {
        if (!$path || !Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->path($path);
    }

    /**
     * Vérifier si un fichier existe
     *
     * @param string $path Chemin du fichier
     * @param string $disk Le disque de stockage (par défaut: 'public')
     * @return bool Le fichier existe-t-il
     */
    public function exists(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Créer un nom de fichier unique basé sur le nom original
     *
     * @param string $originalName Nom original du fichier
     * @return string Nom de fichier unique
     */
    public function generateUniqueFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));

        // Limiter la longueur du basename pour éviter des noms de fichiers trop longs
        if (strlen($basename) > 20) {
            $basename = substr($basename, 0, 20);
        }

        // Ajouter un timestamp et un identifiant aléatoire pour garantir l'unicité
        $timestamp = date('YmdHis');
        $random = Str::random(8);

        return $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * Obtenir la taille d'un fichier en format lisible
     *
     * @param string $path Chemin du fichier
     * @param string $disk Le disque de stockage (par défaut: 'public')
     * @return string|null Taille du fichier (ex: "1.5 MB") ou null si le fichier n'existe pas
     */
    public function getReadableSize(string $path, string $disk = 'public'): ?string
    {
        if (!$this->exists($path, $disk)) {
            return null;
        }

        $size = Storage::disk($disk)->size($path);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $power = $size > 0 ? floor(log($size, 1024)) : 0;

        return number_format($size / pow(1024, $power), 2, '.', ' ') . ' ' . $units[$power];
    }

    /**
     * Créer un répertoire temporaire et y déplacer un fichier
     *
     * @param string $path Chemin du fichier à déplacer
     * @param string $disk Le disque de stockage (par défaut: 'public')
     * @return string|null Chemin temporaire du fichier ou null en cas d'échec
     */
    public function moveToTemp(string $path, string $disk = 'public'): ?string
    {
        if (!$this->exists($path, $disk)) {
            return null;
        }

        $tempDir = 'temp/' . Str::random(10);
        $filename = basename($path);
        $tempPath = $tempDir . '/' . $filename;

        if (Storage::disk($disk)->copy($path, $tempPath)) {
            return $tempPath;
        }

        return null;
    }
}
