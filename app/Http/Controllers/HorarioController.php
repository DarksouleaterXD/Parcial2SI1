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
                'id_docente' => 'nullable|exists:docentes,id',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
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

            // Si no se proporciona id_docente, heredarlo del grupo
            $idDocente = $request->id_docente;
            if (!$idDocente) {
                $grupo = \App\Models\Grupo::find($request->id_grupo);
                $idDocente = $grupo->id_docente;
            }

            $horario = Horario::create([
                'id_grupo' => $request->id_grupo,
                'id_aula' => $request->id_aula,
                'id_docente' => $idDocente,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
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
                'id_docente' => 'nullable|exists:docentes,id',
                'hora_inicio' => 'date_format:H:i',
                'hora_fin' => 'date_format:H:i|after:hora_inicio',
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

            // Preparar datos para actualizar
            $dataToUpdate = $request->only([
                'id_grupo',
                'id_aula',
                'hora_inicio',
                'hora_fin',
                'dia_semana',
                'activo',
                'descripcion',
            ]);

            // Si se cambió el grupo o no se proporciona id_docente, heredarlo del grupo
            if ($request->has('id_grupo') && !$request->has('id_docente')) {
                $grupo = \App\Models\Grupo::find($request->id_grupo);
                $dataToUpdate['id_docente'] = $grupo->id_docente;
            } elseif ($request->has('id_docente')) {
                $dataToUpdate['id_docente'] = $request->id_docente;
            }

            $horario->update($dataToUpdate);

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
