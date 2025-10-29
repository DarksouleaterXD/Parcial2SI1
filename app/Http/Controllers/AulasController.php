<?php

namespace App\Http\Controllers;

use App\Models\Aulas;
use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AulasController extends Controller
{
    /**
     * Display a listing of the aulas.
     * GET /api/aulas
     */
    public function index(Request $request)
    {
        try {
            $query = Aulas::query();

            // Búsqueda
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('codigo', 'LIKE', "%{$search}%")
                    ->orWhere('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('tipo', 'LIKE', "%{$search}%");
            }

            // Paginación
            $perPage = $request->input('per_page', 15);
            $aulas = $query->paginate($perPage);

            // Transformar activo a estado
            $aulas->transform(function ($aula) {
                $aula->activo = $aula->activo ? 'activo' : 'inactivo';
                return $aula;
            });

            return response()->json($aulas);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener aulas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created aula.
     * POST /api/aulas
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'codigo' => 'required|string|max:20|unique:aulas,codigo',
                'nombre' => 'required|string|min:1|max:255',
                'tipo' => 'required|in:teorica,practica,laboratorio,mixta',
                'capacidad' => 'required|integer|min:1|max:500',
                'ubicacion' => 'nullable|string|max:255',
                'piso' => 'nullable|integer|min:0|max:20',
                'activo' => 'required|in:activo,inactivo'
            ]);

            $aula = Aulas::create([
                'codigo' => $validated['codigo'],
                'nombre' => $validated['nombre'],
                'tipo' => $validated['tipo'],
                'capacidad' => $validated['capacidad'],
                'ubicacion' => $validated['ubicacion'] ?? null,
                'piso' => $validated['piso'] ?? null,
                'activo' => $validated['activo'] === 'activo' ? true : false,
            ]);

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->user()->id,
                    'tabla' => 'aulas',
                    'operacion' => 'crear',
                    'id_registro' => $aula->id,
                    'descripcion' => "Aula {$aula->codigo} - {$aula->nombre} creada (capacidad: {$aula->capacidad})"
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Aula creada exitosamente',
                'data' => array_merge($aula->toArray(), ['activo' => $aula->activo ? 'activo' : 'inactivo'])
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
                'message' => 'Error al crear aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified aula.
     * GET /api/aulas/{id}
     */
    public function show($id)
    {
        try {
            $aula = Aulas::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Aula obtenida',
                'data' => array_merge($aula->toArray(), ['activo' => $aula->activo ? 'activo' : 'inactivo'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Aula no encontrada',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified aula.
     * PUT /api/aulas/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $aula = Aulas::findOrFail($id);

            $validated = $request->validate([
                'codigo' => 'sometimes|required|string|max:20|unique:aulas,codigo,' . $id,
                'nombre' => 'sometimes|required|string|min:1|max:255',
                'tipo' => 'sometimes|required|in:teorica,practica,laboratorio,mixta',
                'capacidad' => 'sometimes|required|integer|min:1|max:500',
                'ubicacion' => 'nullable|string|max:255',
                'piso' => 'nullable|integer|min:0|max:20',
                'activo' => 'sometimes|required|in:activo,inactivo'
            ]);

            $cambios = [];
            foreach ($validated as $key => $value) {
                if ($key === 'activo') {
                    $nuevoValor = $value === 'activo' ? true : false;
                    if ($aula->{$key} != $nuevoValor) {
                        $cambios[] = "$key: {$aula->{$key}} → $value";
                        $aula->{$key} = $nuevoValor;
                    }
                } else {
                    if ($aula->{$key} != $value) {
                        $cambios[] = "$key: {$aula->{$key}} → $value";
                        $aula->{$key} = $value;
                    }
                }
            }

            $aula->save();

            // Registrar en bitácora
            if (auth()->check() && !empty($cambios)) {
                Bitacora::create([
                    'id_usuario' => auth()->user()->id,
                    'tabla' => 'aulas',
                    'operacion' => 'editar',
                    'id_registro' => $aula->id,
                    'descripcion' => "Aula {$aula->codigo} actualizada - cambios: " . implode(', ', $cambios)
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Aula actualizada',
                'data' => array_merge($aula->toArray(), ['activo' => $aula->activo ? 'activo' : 'inactivo'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Aula no encontrada',
                'data' => null
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete the specified aula.
     * DELETE /api/aulas/{id}
     */
    public function destroy($id)
    {
        try {
            $aula = Aulas::findOrFail($id);
            $codigoAula = $aula->codigo;

            $aula->delete();

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->user()->id,
                    'tabla' => 'aulas',
                    'operacion' => 'eliminar',
                    'id_registro' => $id,
                    'descripcion' => "Aula ({$codigoAula}) eliminada"
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Aula eliminada correctamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Aula no encontrada',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the state of an aula.
     * PATCH /api/aulas/{id}/estado
     */
    public function updateEstado(Request $request, $id)
    {
        try {
            $aula = Aulas::findOrFail($id);

            $validated = $request->validate([
                'activo' => 'required|in:activo,inactivo'
            ]);

            $estadoAnterior = $aula->activo ? 'activo' : 'inactivo';
            $estadoNuevo = $validated['activo'];

            $aula->activo = $estadoNuevo === 'activo' ? true : false;
            $aula->save();

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->user()->id,
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'aulas',
                    'operacion' => 'cambiar_estado',
                    'id_registro' => $aula->id,
                    'descripcion' => "Estado de aula {$aula->codigo} cambiado de {$estadoAnterior} a {$estadoNuevo}"
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => [
                    'id' => $aula->id,
                    'codigo' => $aula->codigo,
                    'activo' => $estadoNuevo
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Aula no encontrada',
                'data' => null
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar disponibilidad de aulas según criterios
     * GET /api/aulas/disponibilidad
     */
    public function disponibilidad(Request $request)
    {
        try {
            $dia_semana = $request->query('dia_semana'); // 1-5 (Lunes-Viernes)
            $id_bloque = $request->query('id_bloque'); // ID del bloque horario
            $aforo_minimo = $request->query('aforo_minimo', 0);
            $tipo = $request->query('tipo'); // teorica, practica, laboratorio, mixta
            $periodo_id = $request->query('id_periodo'); // ID del periodo

            // Validar parámetros
            if (!$dia_semana || !$id_bloque) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros requeridos: dia_semana, id_bloque'
                ], 400);
            }

            // Obtener todas las aulas activas
            $aulasQuery = Aulas::where('activo', true);

            // Filtrar por tipo si se especifica
            if ($tipo) {
                $aulasQuery->where('tipo', $tipo);
            }

            // Filtrar por aforo mínimo
            if ($aforo_minimo > 0) {
                $aulasQuery->where('capacidad', '>=', $aforo_minimo);
            }

            $aulas = $aulasQuery->get();

            // Obtener horarios ocupados para ese día y bloque
            $ocupadas = \DB::table('horarios')
                ->where('dia_semana', $dia_semana)
                ->where('id_bloque', $id_bloque);

            // Filtrar por período si se especifica
            if ($periodo_id) {
                $ocupadas->where('id_periodo', $periodo_id);
            }

            $ocupadas = $ocupadas->pluck('id_aula')->toArray();

            // Filtrar aulas disponibles (no ocupadas)
            $disponibles = $aulas->filter(function ($aula) use ($ocupadas) {
                return !in_array($aula->id, $ocupadas);
            })->values();

            // Transformar respuesta
            $resultado = $disponibles->map(function ($aula) {
                return [
                    'id' => $aula->id,
                    'codigo' => $aula->codigo,
                    'nombre' => $aula->nombre,
                    'tipo' => $aula->tipo,
                    'capacidad' => $aula->capacidad,
                    'ubicacion' => $aula->ubicacion,
                    'piso' => $aula->piso,
                    'disponible' => true
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Se encontraron " . count($resultado) . " aulas disponibles",
                'data' => $resultado,
                'total' => count($resultado),
                'filtros' => [
                    'dia_semana' => $dia_semana,
                    'id_bloque' => $id_bloque,
                    'aforo_minimo' => $aforo_minimo,
                    'tipo' => $tipo,
                    'periodo_id' => $periodo_id
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar disponibilidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
