<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Personnaliser la gestion des exceptions pour les réponses JSON/API
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($e, $request);
            }
        });
    }

    /**
     * Gérer les exceptions pour les requêtes API.
     */
    private function handleApiException(Throwable $exception, $request)
    {
        // Erreur d'authentification
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Non authentifié. Veuillez vous connecter.',
            ], 401);
        }

        // Erreur de validation
        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => 'Les données fournies sont invalides.',
                'errors' => $exception->errors(),
            ], 422);
        }

        // Modèle non trouvé
        if ($exception instanceof ModelNotFoundException) {
            $modelName = strtolower(class_basename($exception->getModel()));
            return response()->json([
                'message' => "Impossible de trouver {$modelName} avec l'identifiant spécifié.",
            ], 404);
        }

        // Route non trouvée
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'La ressource demandée n\'existe pas.',
            ], 404);
        }

        // Méthode HTTP non autorisée
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'message' => 'La méthode HTTP utilisée n\'est pas autorisée pour cette ressource.',
            ], 405);
        }

        // Erreur d'autorisation
        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'message' => 'Vous n\'avez pas les permissions nécessaires pour effectuer cette action.',
            ], 403);
        }

        // Toutes les autres exceptions
        $statusCode = $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        $message = $statusCode === 500 ? 'Une erreur interne du serveur s\'est produite.' : $exception->getMessage();

        $response = [
            'message' => $message,
        ];

        // En mode debug, ajouter plus de détails sur l'erreur
        if (config('app.debug')) {
            $response['debug'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        }

        return response()->json($response, $statusCode);
    }
}
