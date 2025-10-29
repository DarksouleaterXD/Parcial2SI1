<?php

namespace App\Http\Controllers;

use App\Models\CargaHoraria;
use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;

class CargaHorariaController extends Controller
{
    /**
     * Listar cargas horarias con filtros y paginación
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 10);
            $idDocente = $request->query('id_docente');
            $idGrupo = $request->query('id_grupo');
            $idPeriodo = $request->query('id_periodo');
            $search = $request->query('search', '');

            $query = CargaHoraria::with(['docente', 'grupo', 'periodo']);

            // Filtros específicos
            if ($idDocente) {
                $query->where('id_docente', $idDocente);
            }
            if ($idGrupo) {
                $query->where('id_grupo', $idGrupo);
            }
            if ($idPeriodo) {
                $query->where('id_periodo', $idPeriodo);
            }

            // Búsqueda por nombre de docente o grupo
            if ($search) {
                $query->whereHas('docente', function ($q) use ($search) {
                    $q->whereHas('persona', function ($pq) use ($search) {
                        $pq->where('nombre', 'LIKE', "%$search%");
                    });
                })->orWhereHas('grupo', function ($q) use ($search) {
                    $q->where('id', 'LIKE', "%$search%");
                });
            }

            $cargas = $query->orderBy('id_periodo', 'DESC')
                ->orderBy('id_grupo', 'ASC')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $cargas->items(),
                'pagination' => [
                    'total' => $cargas->total(),
                    'count' => $cargas->count(),
                    'per_page' => $cargas->perPage(),
                    'current_page' => $cargas->currentPage(),
                    'total_pages' => $cargas->lastPage(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar cargas horarias: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva carga horaria
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_docente' => 'required|exists:docentes,id',
                'id_grupo' => 'required|exists:grupos,id',
                'id_periodo' => 'required|exists:periodos,id',
                'horas_semana' => 'required|integer|min:1|max:40',
                'observaciones' => 'string|nullable|max:500',
                'activo' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Verificar que no exista ya una asignación del mismo docente-grupo-periodo
            $existe = CargaHoraria::where('id_docente', $request->id_docente)
                ->where('id_grupo', $request->id_grupo)
                ->where('id_periodo', $request->id_periodo)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una carga horaria para este docente, grupo y periodo',
                ], 400);
            }

            $carga = CargaHoraria::create([
                'id_docente' => $request->id_docente,
                'id_grupo' => $request->id_grupo,
                'id_periodo' => $request->id_periodo,
                'horas_semana' => $request->horas_semana,
                'observaciones' => $request->observaciones,
                'activo' => $request->input('activo', true),
            ]);

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'carga_horarias',
                'operacion' => 'crear',
                'id_registro' => $carga->id,
                'descripcion' => "Carga horaria asignada: Docente {$request->id_docente}, Grupo {$request->id_grupo}, {$request->horas_semana} horas/semana",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Carga horaria asignada exitosamente',
                'data' => $carga->load(['docente', 'grupo', 'periodo']),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar carga horaria: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener una carga horaria específica
     */
    public function show(CargaHoraria $cargaHoraria)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $cargaHoraria->load(['docente', 'grupo', 'periodo']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener carga horaria: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar una carga horaria
     */
    public function update(Request $request, CargaHoraria $cargaHoraria)
    {
        try {
            $validator = Validator::make($request->all(), [
                'horas_semana' => 'integer|min:1|max:40',
                'observaciones' => 'string|nullable|max:500',
                'activo' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $cargaHoraria->update($request->only([
                'horas_semana',
                'observaciones',
                'activo',
            ]));

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'carga_horarias',
                'operacion' => 'editar',
                'id_registro' => $cargaHoraria->id,
                'descripcion' => "Carga horaria actualizada: Docente {$cargaHoraria->id_docente}, Grupo {$cargaHoraria->id_grupo}, {$cargaHoraria->horas_semana} horas/semana",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Carga horaria actualizada exitosamente',
                'data' => $cargaHoraria->load(['docente', 'grupo', 'periodo']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar carga horaria: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar una carga horaria
     */
    public function destroy(CargaHoraria $cargaHoraria)
    {
        try {
            $cargaId = $cargaHoraria->id;
            $cargaHoraria->delete();

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'carga_horarias',
                'operacion' => 'eliminar',
                'id_registro' => $cargaId,
                'descripcion' => "Carga horaria eliminada: ID {$cargaId}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Carga horaria eliminada exitosamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar carga horaria: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener cargas horarias de un grupo en un periodo específico
     */
    public function porGrupoPeriodo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_grupo' => 'required|exists:grupos,id',
                'id_periodo' => 'required|exists:periodos,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $cargas = CargaHoraria::where('id_grupo', $request->id_grupo)
                ->where('id_periodo', $request->id_periodo)
                ->with(['docente', 'grupo', 'periodo'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $cargas,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener cargas horarias de un docente en un periodo
     */
    public function porDocentePeriodo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_docente' => 'required|exists:docentes,id',
                'id_periodo' => 'required|exists:periodos,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $cargas = CargaHoraria::where('id_docente', $request->id_docente)
                ->where('id_periodo', $request->id_periodo)
                ->with(['docente', 'grupo', 'periodo'])
                ->get();

            $totalHoras = $cargas->sum('horas_semana');

            return response()->json([
                'success' => true,
                'data' => $cargas,
                'total_horas_semana' => $totalHoras,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
