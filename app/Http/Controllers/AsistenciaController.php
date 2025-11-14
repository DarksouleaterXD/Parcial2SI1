<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Sesion;
use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AsistenciaController extends Controller
{
    /**
     * CU16.1 - Obtener "Mis clases de hoy" (Docente)
     * GET /api/mis-clases-hoy
     */
    public function misClasesHoy()
    {
        try {
            $user = Auth::user();

            // Obtener el docente asociado al usuario a través de persona
            $persona = $user->persona;

            if (!$persona) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene persona asociada'
                ], 404);
            }

            $docente = Docente::where('id_persona', $persona->id)->first();

            if (!$docente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene perfil de docente'
                ], 404);
            }

            // Obtener sesiones de hoy del docente
            $sesiones = Sesion::with(['horario', 'aula', 'grupo.materia', 'asistencias' => function ($query) use ($docente) {
                $query->where('docente_id', $docente->id);
            }])
                ->hoy()
                ->docente($docente->id)
                ->vigentes()
                ->orderBy('hora_inicio')
                ->get()
                ->map(function ($sesion) {
                    $asistencia = $sesion->asistencias->first();

                    return [
                        'id' => $sesion->id,
                        'materia' => $sesion->grupo->materia->nombre ?? 'Sin materia',
                        'grupo' => $sesion->grupo->paralelo ?? 'Sin grupo',
                        'aula' => $sesion->aula->nombre ?? 'Sin aula',
                        'hora_inicio' => $sesion->hora_inicio,
                        'hora_fin' => $sesion->hora_fin,
                        'estado_sesion' => $sesion->estado,
                        'dentro_ventana' => $sesion->dentroDeVentana(),
                        'ventana_inicio' => $sesion->ventana_inicio,
                        'ventana_fin' => $sesion->ventana_fin,
                        'asistencia_registrada' => $asistencia !== null,
                        'asistencia' => $asistencia ? [
                            'id' => $asistencia->id,
                            'estado' => $asistencia->estado,
                            'metodo' => $asistencia->metodo_registro,
                            'hora_marcado' => $asistencia->hora_marcado,
                            'observacion' => $asistencia->observacion,
                            'validado' => $asistencia->validado,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $sesiones,
                'total' => $sesiones->count(),
                'hora_actual' => Carbon::now()->format('H:i:s'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clases',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CU16.2 - Marcar asistencia (Docente)
     * POST /api/asistencias
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sesion_id' => 'required|exists:sesiones,id',
                'estado' => 'required|in:presente,ausente,retardo,justificado',
                'metodo_registro' => 'required|in:formulario,qr',
                'observacion' => 'nullable|string|max:500',
                'evidencia' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            // Obtener docente a través de persona
            $persona = $user->persona;

            if (!$persona) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene persona asociada'
                ], 404);
            }

            $docente = Docente::where('id_persona', $persona->id)->first();

            if (!$docente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene perfil de docente'
                ], 403);
            }

            // Validar que la sesión exista y sea del docente
            $sesion = Sesion::find($request->sesion_id);

            if (!$sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no encontrada'
                ], 404);
            }

            // Validar que la sesión sea del docente autenticado
            if (!$sesion->esDelDocente($docente->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para marcar asistencia en esta sesión'
                ], 403);
            }

            // Validar ventana de marcado
            if (!$sesion->dentroDeVentana()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fuera de la ventana de marcado',
                    'ventana_inicio' => $sesion->ventana_inicio,
                    'ventana_fin' => $sesion->ventana_fin,
                    'hora_actual' => Carbon::now()->format('H:i:s'),
                ], 400);
            }

            // Validar duplicado
            $asistenciaExistente = Asistencia::where('sesion_id', $sesion->id)
                ->where('docente_id', $docente->id)
                ->first();

            if ($asistenciaExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una asistencia registrada para esta sesión',
                    'asistencia_existente' => [
                        'id' => $asistenciaExistente->id,
                        'estado' => $asistenciaExistente->estado,
                        'hora_marcado' => $asistenciaExistente->hora_marcado,
                    ]
                ], 409);
            }

            // Procesar evidencia si existe
            $evidenciaUrl = null;
            if ($request->hasFile('evidencia')) {
                $evidenciaUrl = $request->file('evidencia')->store('asistencias/evidencias', 'public');
            }

            // Crear asistencia
            $ahora = Carbon::now();
            $asistencia = Asistencia::create([
                'sesion_id' => $sesion->id,
                'docente_id' => $docente->id,
                'estado' => $request->estado,
                'metodo_registro' => $request->metodo_registro,
                'marcado_at' => $ahora,
                'hora_marcado' => $ahora->format('H:i:s'),
                'observacion' => $request->observacion,
                'evidencia_url' => $evidenciaUrl,
                'ip_marcado' => $request->ip(),
                'validado' => false,
            ]);

            // Auto-calcular si es retardo
            if ($asistencia->estado === 'presente' && $asistencia->esRetardo()) {
                $asistencia->update(['estado' => 'retardo']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'data' => [
                    'id' => $asistencia->id,
                    'estado' => $asistencia->estado,
                    'hora_marcado' => $asistencia->hora_marcado,
                    'metodo' => $asistencia->metodo_registro,
                    'observacion' => $asistencia->observacion,
                    'requiere_validacion' => !$asistencia->validado,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CU16.3 - Listar asistencias pendientes de validación (Coordinador)
     * GET /api/asistencias/pendientes
     */
    public function pendientesValidacion(Request $request)
    {
        try {
            $query = Asistencia::with([
                'sesion.horario',
                'sesion.aula',
                'sesion.grupo.materia',
                'docente.persona'
            ])->pendientesValidacion();

            // Filtros opcionales
            if ($request->has('fecha_desde')) {
                $query->whereHas('sesion', function ($q) use ($request) {
                    $q->where('fecha', '>=', $request->fecha_desde);
                });
            }

            if ($request->has('fecha_hasta')) {
                $query->whereHas('sesion', function ($q) use ($request) {
                    $q->where('fecha', '<=', $request->fecha_hasta);
                });
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $asistencias = $query->orderBy('marcado_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $asistencias->map(function ($asistencia) {
                    return [
                        'id' => $asistencia->id,
                        'docente' => $asistencia->docente->persona->nombre ?? 'Sin nombre',
                        'materia' => $asistencia->sesion->grupo->materia->nombre ?? 'Sin materia',
                        'fecha' => $asistencia->sesion->fecha,
                        'hora_clase' => $asistencia->sesion->hora_inicio,
                        'hora_marcado' => $asistencia->hora_marcado,
                        'estado' => $asistencia->estado,
                        'metodo' => $asistencia->metodo_registro,
                        'observacion' => $asistencia->observacion,
                        'evidencia_url' => $asistencia->evidencia_url,
                        'es_retardo' => $asistencia->esRetardo(),
                    ];
                }),
                'pagination' => [
                    'total' => $asistencias->total(),
                    'per_page' => $asistencias->perPage(),
                    'current_page' => $asistencias->currentPage(),
                    'last_page' => $asistencias->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener asistencias pendientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CU16.4 - Validar/Rechazar asistencia (Coordinador)
     * PATCH /api/asistencias/{id}/validar
     */
    public function validar(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'validado' => 'required|boolean',
                'observacion_validacion' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $asistencia = Asistencia::find($id);

            if (!$asistencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asistencia no encontrada'
                ], 404);
            }

            if ($asistencia->validado) {
                return response()->json([
                    'success' => false,
                    'message' => 'La asistencia ya fue validada anteriormente',
                    'validado_por' => $asistencia->validadoPor->nombre ?? 'Desconocido',
                    'validado_at' => $asistencia->validado_at,
                ], 400);
            }

            $user = Auth::user();

            $asistencia->update([
                'validado' => $request->validado,
                'validado_por' => $user->id,
                'validado_at' => Carbon::now(),
                'observacion_validacion' => $request->observacion_validacion,
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->validado ? 'Asistencia validada exitosamente' : 'Asistencia rechazada',
                'data' => [
                    'id' => $asistencia->id,
                    'validado' => $asistencia->validado,
                    'validado_por' => $user->nombre,
                    'validado_at' => $asistencia->validado_at,
                    'observacion_validacion' => $asistencia->observacion_validacion,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CU16.5 - Obtener historial de asistencias (Docente/Coordinador)
     * GET /api/asistencias
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Asistencia::with([
                'sesion.horario',
                'sesion.aula',
                'sesion.grupo.materia',
                'docente.persona',
                'validadoPor'
            ]);

            // Si es docente, solo ve sus propias asistencias
            if ($user->rol === 'docente') {
                $persona = $user->persona;

                if (!$persona) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Usuario no tiene persona asociada'
                    ], 404);
                }

                $docente = Docente::where('id_persona', $persona->id)->first();

                if (!$docente) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Usuario no tiene perfil de docente'
                    ], 403);
                }

                $query->where('docente_id', $docente->id);
            }

            // Filtros
            if ($request->has('fecha_desde')) {
                $query->whereHas('sesion', function ($q) use ($request) {
                    $q->where('fecha', '>=', $request->fecha_desde);
                });
            }

            if ($request->has('fecha_hasta')) {
                $query->whereHas('sesion', function ($q) use ($request) {
                    $q->where('fecha', '<=', $request->fecha_hasta);
                });
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('validado')) {
                $query->where('validado', $request->validado === 'true');
            }

            $asistencias = $query->orderBy('marcado_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $asistencias
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CU16.6 - Ajustar observación (solo si no validada)
     * PATCH /api/asistencias/{id}/observacion
     */
    public function ajustarObservacion(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'observacion' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $asistencia = Asistencia::find($id);

            if (!$asistencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asistencia no encontrada'
                ], 404);
            }

            if (!$asistencia->puedeSerEditada()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede editar una asistencia validada'
                ], 403);
            }

            $asistencia->update(['observacion' => $request->observacion]);

            return response()->json([
                'success' => true,
                'message' => 'Observación actualizada',
                'data' => $asistencia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar observación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

