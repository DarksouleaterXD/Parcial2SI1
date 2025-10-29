<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;

class HorarioController extends Controller
{
    /**
     * Listar horarios con filtros y paginación
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 10);
            $search = $request->query('search', '');
            $idGrupo = $request->query('id_grupo');
            $idAula = $request->query('id_aula');
            $diaSemana = $request->query('dia_semana');
            $idBloque = $request->query('id_bloque');

            $query = Horario::with(['grupo', 'aula', 'docente', 'bloque']);

            // Filtro de búsqueda
            if ($search) {
                $query->whereHas('grupo', function ($q) use ($search) {
                    $q->where('id', 'LIKE', "%$search%");
                })->orWhereHas('aula', function ($q) use ($search) {
                    $q->where('nombre', 'LIKE', "%$search%");
                });
            }

            // Filtros específicos
            if ($idGrupo) {
                $query->where('id_grupo', $idGrupo);
            }
            if ($idAula) {
                $query->where('id_aula', $idAula);
            }
            if ($diaSemana) {
                $query->where('dia_semana', $diaSemana);
            }
            if ($idBloque) {
                $query->where('id_bloque', $idBloque);
            }

            $horarios = $query->orderBy('dia_semana', 'ASC')
                ->orderBy('id_bloque', 'ASC')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $horarios->items(),
                'pagination' => [
                    'total' => $horarios->total(),
                    'count' => $horarios->count(),
                    'per_page' => $horarios->perPage(),
                    'current_page' => $horarios->currentPage(),
                    'total_pages' => $horarios->lastPage(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar horarios: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo horario
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_grupo' => 'required|exists:grupos,id',
                'id_aula' => 'required|exists:aulas,id',
                'id_docente' => 'required|exists:docentes,id',
                'id_bloque' => 'required|exists:bloques_horarios,id',
                'dia_semana' => 'required|string|in:lunes,martes,miércoles,jueves,viernes',
                'activo' => 'boolean',
                'descripcion' => 'string|nullable|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Verificar que no haya conflictos de horarios
            $conflicto = Horario::where('id_aula', $request->id_aula)
                ->where('dia_semana', $request->dia_semana)
                ->where('id_bloque', $request->id_bloque)
                ->where('activo', true)
                ->exists();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'El aula ya tiene un horario asignado en ese bloque y día',
                ], 400);
            }

            // Verificar que el docente no tenga conflicto
            $conflictoDocente = Horario::where('id_docente', $request->id_docente)
                ->where('dia_semana', $request->dia_semana)
                ->where('id_bloque', $request->id_bloque)
                ->where('activo', true)
                ->exists();

            if ($conflictoDocente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El docente ya tiene un horario asignado en ese bloque y día',
                ], 400);
            }

            $horario = Horario::create([
                'id_grupo' => $request->id_grupo,
                'id_aula' => $request->id_aula,
                'id_docente' => $request->id_docente,
                'id_bloque' => $request->id_bloque,
                'dia_semana' => $request->dia_semana,
                'activo' => $request->input('activo', true),
                'descripcion' => $request->descripcion,
            ]);

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'horarios',
                'operacion' => 'crear',
                'id_registro' => $horario->id,
                'descripcion' => "Horario creado: Grupo {$request->id_grupo}, Aula {$request->id_aula}, {$request->dia_semana}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Horario creado exitosamente',
                'data' => $horario->load(['grupo', 'aula', 'docente', 'bloque']),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear horario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener un horario específico
     */
    public function show(Horario $horario)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $horario->load(['grupo', 'aula', 'docente', 'bloque', 'asistencias']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener horario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un horario
     */
    public function update(Request $request, Horario $horario)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_grupo' => 'exists:grupos,id',
                'id_aula' => 'exists:aulas,id',
                'id_docente' => 'exists:docentes,id',
                'id_bloque' => 'exists:bloques_horarios,id',
                'dia_semana' => 'string|in:lunes,martes,miércoles,jueves,viernes',
                'activo' => 'boolean',
                'descripcion' => 'string|nullable|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Verificar conflictos si se cambian valores críticos
            if ($request->has('id_aula') || $request->has('dia_semana') || $request->has('id_bloque')) {
                $idAula = $request->id_aula ?? $horario->id_aula;
                $diaSemana = $request->dia_semana ?? $horario->dia_semana;
                $idBloque = $request->id_bloque ?? $horario->id_bloque;

                $conflicto = Horario::where('id_aula', $idAula)
                    ->where('dia_semana', $diaSemana)
                    ->where('id_bloque', $idBloque)
                    ->where('id', '!=', $horario->id)
                    ->where('activo', true)
                    ->exists();

                if ($conflicto) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El aula ya tiene un horario asignado en ese bloque y día',
                    ], 400);
                }
            }

            if ($request->has('id_docente') || $request->has('dia_semana') || $request->has('id_bloque')) {
                $idDocente = $request->id_docente ?? $horario->id_docente;
                $diaSemana = $request->dia_semana ?? $horario->dia_semana;
                $idBloque = $request->id_bloque ?? $horario->id_bloque;

                $conflictoDocente = Horario::where('id_docente', $idDocente)
                    ->where('dia_semana', $diaSemana)
                    ->where('id_bloque', $idBloque)
                    ->where('id', '!=', $horario->id)
                    ->where('activo', true)
                    ->exists();

                if ($conflictoDocente) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El docente ya tiene un horario asignado en ese bloque y día',
                    ], 400);
                }
            }

            $horario->update($request->only([
                'id_grupo',
                'id_aula',
                'id_docente',
                'id_bloque',
                'dia_semana',
                'activo',
                'descripcion',
            ]));

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'horarios',
                'operacion' => 'editar',
                'id_registro' => $horario->id,
                'descripcion' => "Horario actualizado: Grupo {$horario->id_grupo}, Aula {$horario->id_aula}, {$horario->dia_semana}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Horario actualizado exitosamente',
                'data' => $horario->load(['grupo', 'aula', 'docente', 'bloque']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar horario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un horario
     */
    public function destroy(Horario $horario)
    {
        try {
            $horarioId = $horario->id;
            $horario->delete();

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'horarios',
                'operacion' => 'eliminar',
                'id_registro' => $horarioId,
                'descripcion' => "Horario eliminado: ID {$horarioId}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Horario eliminado exitosamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar horario: ' . $e->getMessage(),
            ], 500);
        }
    }
}
