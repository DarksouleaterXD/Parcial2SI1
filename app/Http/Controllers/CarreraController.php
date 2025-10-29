<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Bitacora;
use App\Helpers\IpHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class CarreraController extends Controller
{
    /**
     * Display a listing of all carreras with optional search and pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search', '');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $query = Carrera::query();

            if ($search) {
                $query->where('nombre', 'ilike', "%{$search}%")
                    ->orWhere('codigo', 'ilike', "%{$search}%")
                    ->orWhere('sigla', 'ilike', "%{$search}%");
            }

            $carreras = $query->orderBy('nombre')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $carreras->items(),
                'pagination' => [
                    'total' => $carreras->total(),
                    'per_page' => $carreras->perPage(),
                    'current_page' => $carreras->currentPage(),
                    'last_page' => $carreras->lastPage()
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener carreras',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created carrera
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255|unique:carreras',
                'codigo' => 'required|string|max:50|unique:carreras',
                'sigla' => 'required|string|max:10|unique:carreras'
            ]);

            $carrera = Carrera::create($validated);

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => auth()->id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'carreras',
                'operacion' => 'crear',
                'id_registro' => $carrera->id,
                'descripcion' => "Carrera creada: {$carrera->nombre} ({$carrera->codigo})"
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Carrera creada exitosamente',
                'data' => $carrera
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear carrera',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified carrera
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $carrera = Carrera::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $carrera
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Carrera no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified carrera
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $carrera = Carrera::findOrFail($id);

            $validated = $request->validate([
                'nombre' => "sometimes|string|max:255|unique:carreras,nombre,{$id}",
                'codigo' => "sometimes|string|max:50|unique:carreras,codigo,{$id}",
                'sigla' => "sometimes|string|max:10|unique:carreras,sigla,{$id}"
            ]);

            $carrera->update($validated);

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => auth()->id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'carreras',
                'operacion' => 'editar',
                'id_registro' => $carrera->id,
                'descripcion' => "Carrera actualizada: {$carrera->nombre} (ID: {$carrera->id})"
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Carrera actualizada exitosamente',
                'data' => $carrera
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar carrera',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete the specified carrera
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $carrera = Carrera::findOrFail($id);

            $nombreCarrera = $carrera->nombre;
            $carrierId = $carrera->id;
            $carrera->delete();

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => auth()->id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'carreras',
                'operacion' => 'eliminar',
                'id_registro' => $carrierId,
                'descripcion' => "Carrera eliminada: {$nombreCarrera}"
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Carrera eliminada exitosamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar carrera',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all materias for a specific carrera
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function materias($id)
    {
        try {
            $carrera = Carrera::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $carrera->materias()->get()
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Carrera no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get all carreras (simple list without pagination - for dropdowns)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function lista()
    {
        try {
            $carreras = Carrera::orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $carreras
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener carreras',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
