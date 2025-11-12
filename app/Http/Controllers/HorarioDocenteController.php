<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Docente;
use App\Models\Periodo;
use App\Models\BloqueHorario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HorarioDocenteController extends Controller
{
    /**
     * Obtener horario semanal del docente autenticado
     * GET /api/docente/mi-horario
     */
    public function miHorario(Request $request)
    {
        $user = Auth::user();

        // Obtener el docente vinculado al usuario
        $docente = Docente::where('id_persona', $user->id_persona)->first();

        if (!$docente) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró registro de docente para este usuario',
            ], 404);
        }

        $periodoId = $request->query('id_periodo');
        $semana = $request->query('semana', 1); // Semana del 1-4

        // Si no especifica periodo, usa el vigente
        if (!$periodoId) {
            $periodo = Periodo::where('vigente', true)->first();
            if (!$periodo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay periodo vigente actualmente',
                ], 404);
            }
            $periodoId = $periodo->id;
        } else {
            $periodo = Periodo::find($periodoId);
            if (!$periodo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Periodo no encontrado',
                ], 404);
            }
        }

        // Obtener horarios del docente en ese periodo
        $horarios = Horario::where('id_docente', $docente->id)
            ->with(['grupo.materia', 'aula', 'bloque'])
            ->whereHas('grupo', function ($query) use ($periodoId) {
                $query->where('id_periodo', $periodoId);
            })
            ->get();

        if ($horarios->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Sin carga asignada para este periodo',
                'data' => [],
                'periodo' => $periodo,
                'docente' => [
                    'id' => $docente->id,
                    'nombre' => $docente->persona->nombre ?? 'Docente',
                ],
            ]);
        }

        // Obtener bloques horarios para formar la grilla
        $bloques = BloqueHorario::orderBy('numero_bloque')->get();

        // Días de la semana
        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

        // Construir grilla semanal
        $grilla = $this->construirGrilla($horarios, $bloques, $diasSemana);

        return response()->json([
            'success' => true,
            'data' => $grilla,
            'periodo' => [
                'id' => $periodo->id,
                'nombre' => $periodo->nombre,
                'fecha_inicio' => $periodo->fecha_inicio,
                'fecha_fin' => $periodo->fecha_fin,
            ],
            'docente' => [
                'id' => $docente->id,
                'nombre' => $docente->persona->nombre ?? 'Docente',
            ],
            'bloques' => $bloques->map(function ($bloque) {
                return [
                    'id' => $bloque->id,
                    'numero' => $bloque->numero_bloque,
                    'nombre' => $bloque->nombre,
                    'hora_inicio' => $bloque->hora_inicio,
                    'hora_fin' => $bloque->hora_fin,
                ];
            }),
        ]);
    }

    /**
     * Obtener horario de un docente específico (para coordinadores)
     * GET /api/coordinador/horario-docente/{id_docente}
     */
    public function horarioDocente($idDocente, Request $request)
    {
        $docente = Docente::find($idDocente);

        if (!$docente) {
            return response()->json([
                'success' => false,
                'message' => 'Docente no encontrado',
            ], 404);
        }

        $periodoId = $request->query('id_periodo');
        $semana = $request->query('semana', 1);

        // Si no especifica periodo, usa el vigente
        if (!$periodoId) {
            $periodo = Periodo::where('vigente', true)->first();
            if (!$periodo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay periodo vigente actualmente',
                ], 404);
            }
            $periodoId = $periodo->id;
        } else {
            $periodo = Periodo::find($periodoId);
            if (!$periodo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Periodo no encontrado',
                ], 404);
            }
        }

        // Obtener horarios del docente
        $horarios = Horario::where('id_docente', $docente->id)
            ->with(['grupo.materia', 'aula', 'bloque'])
            ->whereHas('grupo', function ($query) use ($periodoId) {
                $query->where('id_periodo', $periodoId);
            })
            ->get();

        if ($horarios->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Sin carga asignada para este periodo',
                'data' => [],
                'periodo' => $periodo,
                'docente' => [
                    'id' => $docente->id,
                    'nombre' => $docente->persona->nombre ?? 'Docente',
                ],
            ]);
        }

        // Obtener bloques horarios
        $bloques = BloqueHorario::orderBy('numero_bloque')->get();

        // Días de la semana
        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

        // Construir grilla
        $grilla = $this->construirGrilla($horarios, $bloques, $diasSemana);

        return response()->json([
            'success' => true,
            'data' => $grilla,
            'periodo' => [
                'id' => $periodo->id,
                'nombre' => $periodo->nombre,
                'fecha_inicio' => $periodo->fecha_inicio,
                'fecha_fin' => $periodo->fecha_fin,
            ],
            'docente' => [
                'id' => $docente->id,
                'nombre' => $docente->persona->nombre ?? 'Docente',
            ],
            'bloques' => $bloques->map(function ($bloque) {
                return [
                    'id' => $bloque->id,
                    'numero' => $bloque->numero_bloque,
                    'nombre' => $bloque->nombre,
                    'hora_inicio' => $bloque->hora_inicio,
                    'hora_fin' => $bloque->hora_fin,
                ];
            }),
        ]);
    }

    /**
     * Listar todos los docentes con sus periodos para coordinador
     * GET /api/coordinador/docentes-horarios
     */
    public function listadoDocentesHorarios()
    {
        $docentes = Docente::with(['persona'])
            ->where('activo', true)
            ->get()
            ->map(function ($docente) {
                return [
                    'id' => $docente->id,
                    'nombre' => $docente->persona->nombre ?? 'Docente',
                    'ci' => $docente->persona->ci ?? null,
                ];
            });

        $periodos = Periodo::where('activo', true)->get();

        return response()->json([
            'success' => true,
            'docentes' => $docentes,
            'periodos' => $periodos,
        ]);
    }

    /**
     * Construir grilla semanal a partir de horarios
     */
    private function construirGrilla($horarios, $bloques, $diasSemana)
    {
        // Construir estructura de grilla
        $grilla = [];

        foreach ($bloques as $bloque) {
            $fila = [
                'bloque_id' => $bloque->id,
                'bloque_numero' => $bloque->numero_bloque,
                'bloque_nombre' => $bloque->nombre,
                'hora_inicio' => $bloque->hora_inicio,
                'hora_fin' => $bloque->hora_fin,
            ];

            foreach ($diasSemana as $dia) {
                // Buscar horario para este bloque y día
                $horario = $horarios->first(function ($h) use ($dia, $bloque) {
                    // dias_semana es un JSON array, verificar si contiene el día
                    $dias = is_string($h->dias_semana) ? json_decode($h->dias_semana, true) : $h->dias_semana;
                    return is_array($dias) && in_array($dia, $dias) && $h->id_bloque == $bloque->id;
                });

                if ($horario) {
                    $fila[$dia] = [
                        'id' => $horario->id,
                        'grupo' => $horario->grupo->id ?? null,
                        'materia' => $horario->grupo->materia->nombre ?? 'Sin materia',
                        'aula' => $horario->aula->numero_aula ?? $horario->aula->nombre ?? 'N/A',
                        'paralelo' => $horario->grupo->paralelo ?? 'A',
                    ];
                } else {
                    $fila[$dia] = null;
                }
            }

            $grilla[] = $fila;
        }

        return $grilla;
    }
}
