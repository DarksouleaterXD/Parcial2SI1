<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Bitacora;
use App\Models\User;
use App\Helpers\IpHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GrupoController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/grupos?page=1&search=A&id_materia=1&id_periodo=1&turno=mañana
     */
    public function index(Request $request)
    {
        try {
            $query = Grupo::with(['materia', 'periodo'])
                ->orderBy('created_at', 'desc');

            // Filtro por búsqueda (paralelo)
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('paralelo', 'ilike', "%{$search}%");
            }

            // Filtro por materia
            if ($request->has('id_materia') && $request->id_materia) {
                $query->where('id_materia', $request->id_materia);
            }

            // Filtro por periodo
            if ($request->has('id_periodo') && $request->id_periodo) {
                $query->where('id_periodo', $request->id_periodo);
            }

            // Filtro por turno
            if ($request->has('turno') && $request->turno) {
                $query->where('turno', $request->turno);
            }

            // Paginación
            $grupos = $query->paginate(
                $request->per_page ?? 15,
                ['*'],
                'page',
                $request->page ?? 1
            );

            return response()->json([
                'success' => true,
                'data' => $grupos->items(),
                'pagination' => [
                    'total' => $grupos->total(),
                    'count' => $grupos->count(),
                    'per_page' => $grupos->perPage(),
                    'current_page' => $grupos->currentPage(),
                    'total_pages' => $grupos->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar grupos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/grupos
     */
    public function store(Request $request)
    {
        try {
            // Validar datos entrada
            $validated = $request->validate([
                'id_materia' => ['required', 'integer', 'exists:materias,id'],
                'id_periodo' => ['required', 'integer', 'exists:periodos,id'],
                'paralelo' => ['required', 'string', 'max:1', 'regex:/^[A-Z]$/'],
                'turno' => ['required', 'in:mañana,tarde,noche'],
                'capacidad' => ['required', 'integer', 'min:1', 'max:500'],
            ]);

            // Validar combinación única: materia + periodo + paralelo
            $existe = Grupo::where('id_materia', $validated['id_materia'])
                ->where('id_periodo', $validated['id_periodo'])
                ->where('paralelo', $validated['paralelo'])
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un grupo con esta combinación de materia, periodo y paralelo'
                ], 422);
            }

            // Crear grupo
            $grupo = Grupo::create($validated);

            // Registrar en bitácora
            $user = auth()->user();
            Bitacora::create([
                'id_usuario' => $user->id,
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'grupos',
                'operacion' => 'crear',
                'id_registro' => $grupo->id,
                'descripcion' => "Se creó el grupo {$grupo->paralelo} de la materia ID {$grupo->id_materia} para el periodo ID {$grupo->id_periodo}",
            ]);

            $grupo->load(['materia', 'periodo']);

            return response()->json([
                'success' => true,
                'message' => 'Grupo creado exitosamente',
                'data' => $grupo
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear grupo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/grupos/{id}
     */
    public function show(Grupo $grupo)
    {
        try {
            $grupo->load(['materia', 'periodo', 'docentes', 'horarios', 'aulas']);

            return response()->json([
                'success' => true,
                'data' => $grupo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener grupo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/grupos/{id}
     */
    public function update(Request $request, Grupo $grupo)
    {
        try {
            // Validar datos entrada
            $validated = $request->validate([
                'id_materia' => ['sometimes', 'integer', 'exists:materias,id'],
                'id_periodo' => ['sometimes', 'integer', 'exists:periodos,id'],
                'paralelo' => ['sometimes', 'string', 'max:1', 'regex:/^[A-Z]$/'],
                'turno' => ['sometimes', 'in:mañana,tarde,noche'],
                'capacidad' => ['sometimes', 'integer', 'min:1', 'max:500'],
            ]);

            // Si se cambió materia, periodo o paralelo, validar unicidad
            if (isset($validated['id_materia']) || isset($validated['id_periodo']) || isset($validated['paralelo'])) {
                $id_materia = $validated['id_materia'] ?? $grupo->id_materia;
                $id_periodo = $validated['id_periodo'] ?? $grupo->id_periodo;
                $paralelo = $validated['paralelo'] ?? $grupo->paralelo;

                $existe = Grupo::where('id_materia', $id_materia)
                    ->where('id_periodo', $id_periodo)
                    ->where('paralelo', $paralelo)
                    ->where('id', '!=', $grupo->id)
                    ->exists();

                if ($existe) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya existe otro grupo con esta combinación de materia, periodo y paralelo'
                    ], 422);
                }
            }

            // Guardar cambios previos para bitácora
            $cambios_previos = $grupo->toArray();

            // Actualizar grupo
            $grupo->update($validated);

            // Registrar en bitácora
            $user = auth()->user();
            Bitacora::create([
                'id_usuario' => $user->id,
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'grupos',
                'operacion' => 'editar',
                'id_registro' => $grupo->id,
                'descripcion' => "Se actualizó el grupo {$grupo->paralelo}",
            ]);

            $grupo->load(['materia', 'periodo']);

            return response()->json([
                'success' => true,
                'message' => 'Grupo actualizado exitosamente',
                'data' => $grupo
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar grupo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/grupos/{id}
     */
    public function destroy(Grupo $grupo)
    {
        try {
            $grupo_data = $grupo->toArray();

            // Eliminar grupo
            $grupo->delete();

            // Registrar en bitácora
            $user = auth()->user();
            Bitacora::create([
                'id_usuario' => $user->id,
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'grupos',
                'operacion' => 'eliminar',
                'id_registro' => $grupo->id,
                'descripcion' => "Se eliminó el grupo {$grupo->paralelo}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Grupo eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar grupo: ' . $e->getMessage()
            ], 500);
        }
    }
}
