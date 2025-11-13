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

            $query = Horario::with(['grupo.materia', 'grupo.periodo', 'aula', 'docente.persona', 'bloque']);

            // Filtro de búsqueda
            if ($search) {
                $query->whereHas('grupo.materia', function ($q) use ($search) {
                    $q->where('nombre', 'ILIKE', "%$search%");
                })->orWhereHas('aula', function ($q) use ($search) {
                    $q->where('nombre', 'ILIKE', "%$search%");
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
                // Buscar en el array JSON de días
                $query->whereJsonContains('dias_semana', $diaSemana);
            }
            if ($idBloque) {
                $query->where('id_bloque', $idBloque);
            }

            $horarios = $query->orderBy('id_bloque', 'ASC')
                ->orderBy('created_at', 'DESC')
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
            // Log para debugging
            \Log::info('Datos recibidos en store:', $request->all());
            \Log::info('dias_semana específicamente:', ['dias' => $request->dias_semana]);

            // Validación básica
            $validator = Validator::make($request->all(), [
                'id_grupo' => 'required|exists:grupos,id',
                'id_aula' => 'required|exists:aulas,id',
                'id_bloque' => 'required|exists:bloques_horarios,id',
                'dias_semana' => 'required|array|min:1',
                'dias_semana.*' => 'required|string',
                'activo' => 'boolean',
                'descripcion' => 'string|nullable|max:500',
            ]);

            if ($validator->fails()) {
                \Log::error('Validación fallida:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Normalizar días de la semana (convertir a formato estándar)
            $diasNormalizados = array_map(function($dia) {
                $mapa = [
                    'lunes' => 'Lunes',
                    'martes' => 'Martes',
                    'miercoles' => 'Miércoles',
                    'miércoles' => 'Miércoles',
                    'jueves' => 'Jueves',
                    'viernes' => 'Viernes',
                    'sabado' => 'Sábado',
                    'sábado' => 'Sábado',
                    'Lunes' => 'Lunes',
                    'Martes' => 'Martes',
                    'Miércoles' => 'Miércoles',
                    'Miercoles' => 'Miércoles',
                    'Jueves' => 'Jueves',
                    'Viernes' => 'Viernes',
                    'Sábado' => 'Sábado',
                    'Sabado' => 'Sábado',
                ];

                $diaNormalizado = $mapa[trim($dia)] ?? null;

                if (!$diaNormalizado) {
                    \Log::error("Día no reconocido: '{$dia}'");
                }

                return $diaNormalizado;
            }, $request->dias_semana);

            // Validar que todos los días fueron normalizados correctamente
            if (in_array(null, $diasNormalizados, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uno o más días de la semana no son válidos',
                    'errors' => ['dias_semana' => ['Días permitidos: lunes, martes, miercoles, jueves, viernes, sabado']],
                ], 422);
            }

            // Heredar docente del grupo
            $grupo = \App\Models\Grupo::find($request->id_grupo);
            $idDocente = $grupo->id_docente;

            // Verificar conflictos de horario para cada día (usando días normalizados)
            foreach ($diasNormalizados as $dia) {
                $conflicto = Horario::where('id_aula', $request->id_aula)
                    ->where('id_bloque', $request->id_bloque)
                    ->whereJsonContains('dias_semana', $dia)
                    ->where('activo', true)
                    ->first();

                if ($conflicto) {
                    return response()->json([
                        'success' => false,
                        'message' => "El aula ya está ocupada el día {$dia} en este bloque horario",
                    ], 422);
                }
            }

            $horario = Horario::create([
                'id_grupo' => $request->id_grupo,
                'id_aula' => $request->id_aula,
                'id_docente' => $idDocente,
                'id_bloque' => $request->id_bloque,
                'dias_semana' => $diasNormalizados, // Usar días normalizados
                'activo' => $request->input('activo', true),
                'descripcion' => $request->descripcion,
            ]);

            // Registrar en bitácora
            $diasStr = implode(', ', $request->dias_semana);
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'horarios',
                'operacion' => 'crear',
                'id_registro' => $horario->id,
                'descripcion' => "Horario creado: Grupo {$request->id_grupo}, Aula {$request->id_aula}, Días: {$diasStr}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Horario creado exitosamente',
                'data' => $horario->load(['grupo.materia', 'grupo.periodo', 'aula', 'docente.persona', 'bloque']),
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
            \Log::info('UPDATE HORARIO - Datos recibidos:', $request->all());
            
            $validator = Validator::make($request->all(), [
                'id_grupo' => 'exists:grupos,id',
                'id_aula' => 'exists:aulas,id',
                'id_bloque' => 'exists:bloques_horarios,id',
                'dias_semana' => 'array|min:1',
                'dias_semana.*' => 'required|string',
                'activo' => 'boolean',
                'descripcion' => 'string|nullable|max:500',
            ]);

            if ($validator->fails()) {
                \Log::error('UPDATE HORARIO - Error de validación:', [
                    'errors' => $validator->errors(),
                    'datos' => $request->all()
                ]);
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
                'id_bloque',
                'activo',
                'descripcion',
            ]);

            // Normalizar días de la semana si se envían
            if ($request->has('dias_semana')) {
                $diasNormalizados = array_map(function($dia) {
                    $mapa = [
                        'lunes' => 'Lunes',
                        'martes' => 'Martes',
                        'miercoles' => 'Miércoles',
                        'miércoles' => 'Miércoles',
                        'jueves' => 'Jueves',
                        'viernes' => 'Viernes',
                        'sabado' => 'Sábado',
                        'sábado' => 'Sábado',
                        'domingo' => 'Domingo',
                        'Lunes' => 'Lunes',
                        'Martes' => 'Martes',
                        'Miércoles' => 'Miércoles',
                        'Miercoles' => 'Miércoles',
                        'Jueves' => 'Jueves',
                        'Viernes' => 'Viernes',
                        'Sábado' => 'Sábado',
                        'Sabado' => 'Sábado',
                        'Domingo' => 'Domingo',
                    ];
                    return $mapa[trim($dia)] ?? $dia;
                }, $request->dias_semana);

                $dataToUpdate['dias_semana'] = $diasNormalizados;
            }

            // Si se cambió el grupo, actualizar docente
            if ($request->has('id_grupo')) {
                $grupo = \App\Models\Grupo::find($request->id_grupo);
                $dataToUpdate['id_docente'] = $grupo->id_docente;
            }

            // Verificar conflictos de horario si se cambia aula, bloque o días
            if ($request->has('dias_semana') || $request->has('id_aula') || $request->has('id_bloque')) {
                $idAula = $request->id_aula ?? $horario->id_aula;
                $idBloque = $request->id_bloque ?? $horario->id_bloque;
                $diasSemana = $dataToUpdate['dias_semana'] ?? $horario->dias_semana;

                foreach ($diasSemana as $dia) {
                    $conflicto = Horario::where('id_aula', $idAula)
                        ->where('id_bloque', $idBloque)
                        ->whereJsonContains('dias_semana', $dia)
                        ->where('id', '!=', $horario->id)
                        ->where('activo', true)
                        ->first();

                    if ($conflicto) {
                        return response()->json([
                            'success' => false,
                            'message' => "El aula ya está ocupada el día {$dia} en este bloque horario",
                        ], 422);
                    }
                }
            }

            $horario->update($dataToUpdate);

            // Registrar en bitácora
            $diasStr = implode(', ', $horario->dias_semana ?? []);
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'horarios',
                'operacion' => 'editar',
                'id_registro' => $horario->id,
                'descripcion' => "Horario actualizado: Grupo {$horario->id_grupo}, Aula {$horario->id_aula}, Días: {$diasStr}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Horario actualizado exitosamente',
                'data' => $horario->load(['grupo.materia', 'grupo.periodo', 'aula', 'docente.persona', 'bloque']),
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
