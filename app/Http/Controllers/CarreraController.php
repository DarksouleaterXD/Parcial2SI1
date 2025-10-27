<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CarreraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Carrera::all(), 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'required|string|unique:carreras',
            'descripcion' => 'nullable|string'
        ]);

        $carrera = Carrera::create($validated);
        return response()->json($carrera, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Carrera $carrera)
    {
        return response()->json($carrera, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Carrera $carrera)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Carrera $carrera)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'codigo' => 'sometimes|string|unique:carreras,codigo,' . $carrera->id,
            'descripcion' => 'nullable|string'
        ]);

        $carrera->update($validated);
        return response()->json($carrera, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Carrera $carrera)
    {
        $carrera->delete();
        return response()->json(null, 204);
    }

    /**
     * Get materias related to carrera
     */
    public function materias(Carrera $carrera)
    {
        return response()->json($carrera->materias, 200);
    }
}
