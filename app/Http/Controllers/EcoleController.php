<?php

namespace App\Http\Controllers;

use App\Models\Ecole;
use Illuminate\Http\Request;

class EcoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ecoles = Ecole::all();
        return response()->json($ecoles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'site_web' => 'nullable|url|max:255',
            'logo' => 'nullable|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'directeur' => 'nullable|string|max:255',
            'date_creation' => 'nullable|date',
        ]);

        $ecole = Ecole::create($validated);

        return response()->json([
            'message' => 'École créée avec succès',
            'ecole' => $ecole
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ecole = Ecole::findOrFail($id);
        return response()->json($ecole);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ecole = Ecole::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'adresse' => 'sometimes|required|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'site_web' => 'nullable|url|max:255',
            'logo' => 'nullable|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'directeur' => 'nullable|string|max:255',
            'date_creation' => 'nullable|date',
        ]);

        $ecole->update($validated);

        return response()->json([
            'message' => 'École mise à jour avec succès',
            'ecole' => $ecole
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ecole = Ecole::findOrFail($id);
        $ecole->delete();

        return response()->json([
            'message' => 'École supprimée avec succès'
        ]);
    }
}
