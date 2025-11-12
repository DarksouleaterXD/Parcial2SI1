<?php

namespace App\Http\Controllers;

use App\Models\Sesion;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SesionController extends Controller
{
    /**
     * Generar sesiones automáticamente desde horarios
     * POST /api/sesiones/generar
     */
    public function generarSesiones(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'horario_ids' => 'nullable|array',
                'horario_ids.*' => 'exists:horarios,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin = Carbon::parse($request->fecha_fin);

            // Obtener horarios a procesar
            $query = Horario::with(['grupo', 'aula', 'docente'])->where('activo', true);

            if ($request->has('horario_ids')) {
                $query->whereIn('id', $request->horario_ids);
            }

            $horarios = $query->get();
            $sesionesCreadas = 0;

            // Mapeo de días
            $diasSemana = [
                'Lunes' => Carbon::MONDAY,
                'Martes' => Carbon::TUESDAY,
                'Miércoles' => Carbon::WEDNESDAY,
                'Jueves' => Carbon::THURSDAY,
                'Viernes' => Carbon::FRIDAY,
                'Sábado' => Carbon::SATURDAY,
                'Domingo' => Carbon::SUNDAY,
            ];

            foreach ($horarios as $horario) {
                $diaNum = $diasSemana[$horario->dia_semana] ?? null;

                if (!$diaNum) continue;

                // Generar sesiones para cada semana en el rango
                $fecha = $fechaInicio->copy();

                while ($fecha->lte($fechaFin)) {
                    // Buscar el día de la semana correspondiente
                    $fechaSesion = $fecha->copy()->next($diaNum);

                    if ($fechaSesion->lte($fechaFin)) {
                        // Verificar si ya existe
                        $existe = Sesion::where('horario_id', $horario->id)
                            ->where('fecha', $fechaSesion->format('Y-m-d'))
                            ->exists();

                        if (!$existe) {
                            $sesion = Sesion::create([
                                'horario_id' => $horario->id,
                                'docente_id' => $horario->id_docente,
                                'aula_id' => $horario->id_aula,
                                'grupo_id' => $horario->id_grupo,
                                'fecha' => $fechaSesion->format('Y-m-d'),
                                'hora_inicio' => $horario->hora_inicio,
                                'hora_fin' => $horario->hora_fin,
                                'estado' => 'programada',
                                'activo' => true,
                            ]);

                            // Calcular ventana de marcado
                            $sesion->calcularVentanaMarcado();
                            $sesion->save();

                            $sesionesCreadas++;
                        }
                    }

                    $fecha->addWeek();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Se generaron {$sesionesCreadas} sesiones",
                'sesiones_creadas' => $sesionesCreadas,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar sesiones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar sesiones
     * GET /api/sesiones
     */
    public function index(Request $request)
    {
        try {
            $query = Sesion::with(['horario', 'aula', 'grupo.materia', 'docente.persona', 'asistencias']);

            // Filtros
            if ($request->has('fecha')) {
                $query->where('fecha', $request->fecha);
            }

            if ($request->has('docente_id')) {
                $query->where('docente_id', $request->docente_id);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $sesiones = $query->orderBy('fecha', 'desc')->orderBy('hora_inicio')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sesiones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar sesión
     * PATCH /api/sesiones/{id}/cancelar
     */
    public function cancelar(Request $request, $id)
    {
        try {
            $sesion = Sesion::find($id);

            if (!$sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no encontrada'
                ], 404);
            }

            $sesion->update([
                'estado' => 'cancelada',
                'observaciones' => $request->observaciones ?? 'Sesión cancelada',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sesión cancelada exitosamente',
                'data' => $sesion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
