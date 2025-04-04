<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Vérifier que l'utilisateur est authentifié
        if (!$request->user()) {
            return response()->json([
                'message' => 'Non autorisé. Veuillez vous connecter.'
            ], 401);
        }

        // Vérifier que l'utilisateur a le rôle requis
        if ($request->user()->role !== $role) {
            return response()->json([
                'message' => 'Non autorisé. Vous n\'avez pas les permissions nécessaires.'
            ], 403);
        }

        return $next($request);
    }
}
