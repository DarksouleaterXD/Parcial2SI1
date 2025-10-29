<?php

namespace App\Http\Controllers;

use App\Models\BloqueHorario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class BloqueHorarioController extends Controller
{
    /**
     * Listar bloques de horarios con paginación y filtros
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 10);
            $search = $request->query('search', '');
            $activo = $request->query('activo');

            $query = BloqueHorario::query();

            // Filtro de búsqueda
            if ($search) {
                $query->where('nombre', 'LIKE', "%$search%");
            }

            // Filtro de estado
            if ($activo !== null && $activo !== '') {
                $query->where('activo', $activo === 'true' || $activo === '1');
            }

            // Ordenar por número de bloque
            $bloques = $query->orderBy('numero_bloque', 'ASC')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $bloques->items(),
                'pagination' => [
                    'total' => $bloques->total(),
                    'count' => $bloques->count(),
                    'per_page' => $bloques->perPage(),
                    'current_page' => $bloques->currentPage(),
                    'total_pages' => $bloques->lastPage(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar bloques de horarios: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo bloque de horario
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'numero_bloque' => 'required|integer|unique:bloques_horarios,numero_bloque',
                'activo' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $bloque = BloqueHorario::create([
                'nombre' => $request->nombre,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'numero_bloque' => $request->numero_bloque,
                'activo' => $request->input('activo', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bloque de horario creado exitosamente',
                'data' => $bloque,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear bloque de horario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener un bloque de horario específico
     */
    public function show(BloqueHorario $bloqueHorario)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $bloqueHorario->load('horarios'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener bloque de horario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un bloque de horario
     */
    public function update(Request $request, BloqueHorario $bloqueHorario)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'string|max:100',
                'hora_inicio' => 'date_format:H:i',
                'hora_fin' => 'date_format:H:i|after:hora_inicio',
                'numero_bloque' => 'integer|unique:bloques_horarios,numero_bloque,' . $bloqueHorario->id,
                'activo' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $bloqueHorario->update($request->only([
                'nombre',
                'hora_inicio',
                'hora_fin',
                'numero_bloque',
                'activo',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Bloque de horario actualizado exitosamente',
                'data' => $bloqueHorario,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar bloque de horario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un bloque de horario
     */
    public function destroy(BloqueHorario $bloqueHorario)
    {
        try {
            // Verificar si hay horarios asociados
            if ($bloqueHorario->horarios()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el bloque. Tiene horarios asignados.',
                ], 400);
            }

            $bloqueHorario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bloque de horario eliminado exitosamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar bloque de horario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar estado de un bloque de horario
     */
    public function updateEstado(Request $request, BloqueHorario $bloqueHorario)
    {
        try {
            $validator = Validator::make($request->all(), [
                'activo' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $bloqueHorario->update(['activo' => $request->activo]);

            return response()->json([
                'success' => true,
                'message' => 'Estado del bloque actualizado exitosamente',
                'data' => $bloqueHorario,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage(),
            ], 500);
        }
    }
}
