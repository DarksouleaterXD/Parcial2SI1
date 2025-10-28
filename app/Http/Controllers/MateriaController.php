<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Bitacora;
use Carbon\Carbon;

class MateriaController extends Controller
{
    /**
     * Obtener el ID del usuario autenticado o retornar null de forma segura
     */
    private function getUserId()
    {
        try {
            $userId = auth()->id();
            if (!$userId) {
                $userId = auth()->user()?->id;
            }
            return $userId;
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo auth()->id(): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Display a listing of the materias.
     * GET /api/materias
     */
    public function index(Request $request)
    {
        try {
            $query = Materia::with('carrera');

            // Búsqueda
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('codigo', 'LIKE', "%{$search}%")
                    ->orWhere('nombre', 'LIKE', "%{$search}%")
                    ->orWhereHas('carrera', function ($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%");
                    });
            }

            // Paginación
            $perPage = $request->input('per_page', 15);
            $materias = $query->paginate($perPage);

            // Transformar activo a estado
            $materias->transform(function ($materia) {
                $materia->activo = $materia->activo ? 'activo' : 'inactivo';
                return $materia;
            });

            return response()->json($materias);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener materias: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created materia in storage.
     * POST /api/materias
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('materias', 'codigo'),
            ],
            'nombre' => 'required|string|max:255',
            'carrera_id' => 'nullable|exists:carreras,id',
            'horas_semana' => 'required|integer|between:1,40',
            'activo' => 'required|in:activo,inactivo',
        ], [
            'codigo.required' => 'El código de materia es obligatorio',
            'codigo.unique' => 'El código ya está registrado',
            'nombre.required' => 'El nombre de la materia es obligatorio',
            'horas_semana.required' => 'Las horas por semana son obligatorias',
            'horas_semana.between' => 'Las horas deben estar entre 1 y 40',
            'activo.required' => 'El estado es obligatorio',
        ]);

        try {
            // Convertir estado a boolean
            $activo = $validated['activo'] === 'activo';

            $materia = Materia::create([
                'codigo' => strtoupper($validated['codigo']),
                'nombre' => $validated['nombre'],
                'carrera_id' => $validated['carrera_id'] ?? null,
                'horas_semana' => $validated['horas_semana'],
                'activo' => $activo,
            ]);

            // Registrar en bitácora - Solo si hay usuario autenticado
            $userId = $this->getUserId();
            if ($userId) {
                Bitacora::create([
                    'id_usuario' => $userId,
                    'tabla' => 'materias',
                    'operacion' => 'crear',
                    'id_registro' => $materia->id,
                    'descripcion' => "Se creó materia: {$materia->codigo} - {$materia->nombre}",
                ]);
            } else {
                \Log::warning('No se pudo registrar en bitácora: usuario no autenticado');
            }

            // Cargar relación carrera
            $materia->load('carrera');
            $materia->activo = $activo ? 'activo' : 'inactivo';

            return response()->json([
                'success' => true,
                'data' => $materia,
                'message' => 'Materia creada correctamente',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear materia: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified materia.
     * GET /api/materias/:id
     */
    public function show(Materia $materia)
    {
        try {
            $materia->load('carrera');
            $materia->activo = $materia->activo ? 'activo' : 'inactivo';

            return response()->json([
                'success' => true,
                'data' => $materia,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener materia: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified materia in storage.
     * PUT /api/materias/:id
     */
    public function update(Request $request, Materia $materia)
    {
        $validated = $request->validate([
            'codigo' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('materias', 'codigo')->ignore($materia->id),
            ],
            'nombre' => 'sometimes|string|max:255',
            'carrera_id' => 'nullable|exists:carreras,id',
            'horas_semana' => 'sometimes|integer|between:1,40',
            'activo' => 'sometimes|in:activo,inactivo',
        ]);

        try {
            $cambios = [];
            $updateData = [];

            // Verificar cambios
            if (isset($validated['codigo']) && $validated['codigo'] !== $materia->codigo) {
                $cambios[] = "Código: {$materia->codigo} → {$validated['codigo']}";
                $updateData['codigo'] = strtoupper($validated['codigo']);
            }

            if (isset($validated['nombre']) && $validated['nombre'] !== $materia->nombre) {
                $cambios[] = "Nombre: {$materia->nombre} → {$validated['nombre']}";
                $updateData['nombre'] = $validated['nombre'];
            }

            if (isset($validated['horas_semana']) && $validated['horas_semana'] != $materia->horas_semana) {
                $cambios[] = "Horas: {$materia->horas_semana} → {$validated['horas_semana']}";
                $updateData['horas_semana'] = $validated['horas_semana'];
            }

            if (isset($validated['carrera_id']) && $validated['carrera_id'] != $materia->carrera_id) {
                $carreraAnterior = $materia->carrera?->nombre ?? 'Sin carrera';
                $carreraNueva = isset($validated['carrera_id'])
                    ? \App\Models\Carrera::find($validated['carrera_id'])?->nombre ?? 'Sin carrera'
                    : 'Sin carrera';
                $cambios[] = "Carrera: {$carreraAnterior} → {$carreraNueva}";
                $updateData['carrera_id'] = $validated['carrera_id'];
            }

            if (isset($validated['activo'])) {
                $nuevoActivo = $validated['activo'] === 'activo';
                if ($nuevoActivo != $materia->activo) {
                    $estadoAnterior = $materia->activo ? 'activo' : 'inactivo';
                    $estadoNuevo = $nuevoActivo ? 'activo' : 'inactivo';
                    $cambios[] = "Estado: {$estadoAnterior} → {$estadoNuevo}";
                    $updateData['activo'] = $nuevoActivo;
                }
            }

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay cambios que guardar',
                ], 422);
            }

            $materia->update($updateData);

            // Registrar en bitácora - Solo si hay usuario autenticado
            $userId = $this->getUserId();
            if ($userId) {
                Bitacora::create([
                    'id_usuario' => $userId,
                    'tabla' => 'materias',
                    'operacion' => 'editar',
                    'id_registro' => $materia->id,
                    'descripcion' => "Se actualizó materia {$materia->codigo}: " . implode(', ', $cambios),
                ]);
            }

            $materia->load('carrera');
            $materia->activo = $materia->activo ? 'activo' : 'inactivo';

            return response()->json([
                'success' => true,
                'data' => $materia,
                'message' => 'Materia actualizada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar materia: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the estado (activo/inactivo) of a materia.
     * PATCH /api/materias/:id/estado
     */
    public function updateEstado(Request $request, Materia $materia)
    {
        $materia->load('carrera');

        $validated = $request->validate([
            'activo' => 'required|in:activo,inactivo',
        ]);

        try {
            $nuevoActivo = $validated['activo'] === 'activo';
            $estadoAnterior = $materia->activo ? 'activo' : 'inactivo';
            $estadoNuevo = $nuevoActivo ? 'activo' : 'inactivo';

            if ($estadoAnterior === $estadoNuevo) {
                return response()->json([
                    'success' => false,
                    'message' => "La materia ya está {$estadoNuevo}",
                ], 422);
            }

            $materia->update(['activo' => $nuevoActivo]);

            // Registrar en bitácora - Solo si hay usuario autenticado
            $userId = $this->getUserId();
            if ($userId) {
                Bitacora::create([
                    'id_usuario' => $userId,
                    'tabla' => 'materias',
                    'operacion' => 'cambiar_estado',
                    'id_registro' => $materia->id,
                    'descripcion' => "Estado de materia {$materia->codigo} cambió de {$estadoAnterior} a {$estadoNuevo}",
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $materia->id,
                    'activo' => $estadoNuevo,
                ],
                'message' => 'Estado actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified materia from storage.
     * DELETE /api/materias/:id
     */
    public function destroy(Materia $materia)
    {
        try {
            $codigoMateria = $materia->codigo;
            $nombreMateria = $materia->nombre;

            // Registrar eliminación en bitácora - Solo si hay usuario autenticado
            $userId = $this->getUserId();
            if ($userId) {
                Bitacora::create([
                    'id_usuario' => $userId,
                    'tabla' => 'materias',
                    'operacion' => 'eliminar',
                    'id_registro' => $materia->id,
                    'descripcion' => "Se eliminó materia: {$codigoMateria} - {$nombreMateria}",
                ]);
            }

            $materia->delete();

            return response()->json([
                'success' => true,
                'message' => 'Materia eliminada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar materia: ' . $e->getMessage(),
            ], 500);
        }
    }
}
