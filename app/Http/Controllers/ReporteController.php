<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Aulas;
use App\Models\BloqueHorario;
use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    /**
     * Reporte de Horarios Semanales
     */
    public function horariosSemanales(Request $request)
    {
        try {
            $periodo_id = $request->query('periodo_id');
            $carrera_id = $request->query('carrera_id');
            $docente_id = $request->query('docente_id');

            if (!$periodo_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El periodo es requerido'
                ], 400);
            }

            $query = Horario::with([
                'bloque',
                'grupo.materia.carrera',
                'grupo.periodo',
                'aula',
                'docente.persona'
            ])
            ->whereHas('grupo', function($q) use ($periodo_id) {
                $q->where('id_periodo', $periodo_id);
            });

            // Filtrar por carrera si se proporciona
            if ($carrera_id) {
                $query->whereHas('grupo.materia', function($q) use ($carrera_id) {
                    $q->where('id_carrera', $carrera_id);
                });
            }

            // Filtrar por docente si se proporciona
            if ($docente_id) {
                $query->where('id_docente', $docente_id);
            }

            $horarios = $query->get();

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => auth()->id(),
                'ip_address' => \App\Helpers\IpHelper::getClientIp(),
                'tabla' => 'horarios',
                'operacion' => 'exportacion',
                'descripcion' => "Consultó reporte de horarios semanales (Periodo: $periodo_id)"
            ]);

            return response()->json([
                'success' => true,
                'data' => $horarios
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en horariosSemanales:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar Horarios a PDF
     */
    public function horariosSemanalesPDF(Request $request)
    {
        try {
            $periodo_id = $request->query('periodo_id');
            $carrera_id = $request->query('carrera_id');
            $docente_id = $request->query('docente_id');

            $query = Horario::with([
                'bloque',
                'grupo.materia.carrera',
                'grupo.periodo',
                'aula',
                'docente.persona'
            ])
            ->whereHas('grupo', function($q) use ($periodo_id) {
                $q->where('id_periodo', $periodo_id);
            });

            if ($carrera_id) {
                $query->whereHas('grupo.materia', function($q) use ($carrera_id) {
                    $q->where('id_carrera', $carrera_id);
                });
            }

            if ($docente_id) {
                $query->where('id_docente', $docente_id);
            }

            $horarios = $query->get();

            // Obtener nombre del periodo
            $periodo = $horarios->first()?->grupo?->periodo;

            $data = [
                'horarios' => $horarios,
                'periodo' => $periodo,
                'fecha_generacion' => now()->format('d/m/Y H:i')
            ];

            $pdf = Pdf::loadView('reportes.horarios-semanales', $data);

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => auth()->id(),
                'ip_address' => \App\Helpers\IpHelper::getClientIp(),
                'tabla' => 'horarios',
                'operacion' => 'exportacion',
                'descripcion' => "Exportó reporte de horarios semanales a PDF"
            ]);

            return $pdf->download('horarios_semanales_' . time() . '.pdf');

        } catch (\Exception $e) {
            \Log::error('Error en horariosSemanalesPDF:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar Horarios a Excel
     */
    public function horariosSemanalesExcel(Request $request)
    {
        try {
            $periodo_id = $request->query('periodo_id');
            $carrera_id = $request->query('carrera_id');
            $docente_id = $request->query('docente_id');

            $query = Horario::with([
                'bloque',
                'grupo.materia.carrera',
                'grupo.periodo',
                'aula',
                'docente.persona'
            ])
            ->whereHas('grupo', function($q) use ($periodo_id) {
                $q->where('id_periodo', $periodo_id);
            });

            if ($carrera_id) {
                $query->whereHas('grupo.materia', function($q) use ($carrera_id) {
                    $q->where('id_carrera', $carrera_id);
                });
            }

            if ($docente_id) {
                $query->where('id_docente', $docente_id);
            }

            $horarios = $query->get();

            // Crear CSV
            $filename = 'horarios_semanales_' . time() . '.csv';
            $handle = fopen('php://temp', 'r+');

            // Encabezados
            fputcsv($handle, ['Días', 'Materia', 'Bloque', 'Horario', 'Grupo', 'Aula', 'Docente']);

            // Datos
            foreach ($horarios as $h) {
                fputcsv($handle, [
                    implode(', ', $h->dias_semana ?? []),
                    $h->grupo->materia->nombre ?? 'N/A',
                    $h->bloque->nombre ?? 'N/A',
                    ($h->bloque->hora_inicio ?? '') . ' - ' . ($h->bloque->hora_fin ?? ''),
                    $h->grupo->codigo ?? 'N/A',
                    $h->aula->numero_aula ?? 'N/A',
                    ($h->docente->persona->nombre ?? '') . ' ' . ($h->docente->persona->apellido_paterno ?? '')
                ]);
            }

            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => auth()->id(),
                'ip_address' => \App\Helpers\IpHelper::getClientIp(),
                'tabla' => 'horarios',
                'operacion' => 'exportacion',
                'descripcion' => "Exportó reporte de horarios semanales a Excel/CSV"
            ]);

            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de Aulas Disponibles
     */
    public function aulasDisponibles(Request $request)
    {
        try {
            $dia = $request->query('dia'); // Opcional: Lunes, Martes, etc.
            $aula_id = $request->query('aula_id'); // Opcional: Filtrar por aula específica
            $bloque_id = $request->query('bloque_id'); // Opcional: Filtrar por bloque específico

            // Obtener todas las aulas activas (o una específica)
            $aulasQuery = Aulas::where('activo', true);
            if ($aula_id) {
                $aulasQuery->where('id', $aula_id);
            }
            $aulas = $aulasQuery->get();

            // Obtener todos los bloques horarios (o uno específico)
            $bloquesQuery = BloqueHorario::orderBy('numero_bloque');
            if ($bloque_id) {
                $bloquesQuery->where('id', $bloque_id);
            }
            $bloques = $bloquesQuery->get();

            // Obtener todos los horarios ocupados de una sola vez
            $horariosQuery = Horario::select('id_aula', 'id_bloque', 'dias_semana');

            if ($dia) {
                $horariosQuery->whereJsonContains('dias_semana', $dia);
            }

            $horariosOcupados = $horariosQuery->get();

            // Crear un set de combinaciones aula-bloque ocupadas para búsqueda rápida
            $ocupados = [];
            foreach ($horariosOcupados as $horario) {
                $key = $horario->id_aula . '-' . $horario->id_bloque;
                $ocupados[$key] = true;
            }

            // Construir el resultado
            $resultado = [];

            foreach ($aulas as $aula) {
                $bloques_disponibles = [];

                foreach ($bloques as $bloque) {
                    $key = $aula->id . '-' . $bloque->id;
                    $ocupada = isset($ocupados[$key]);

                    $bloques_disponibles[] = [
                        'bloque' => $bloque->nombre,
                        'hora_inicio' => date('H:i', strtotime($bloque->hora_inicio)),
                        'hora_fin' => date('H:i', strtotime($bloque->hora_fin)),
                        'disponible' => !$ocupada
                    ];
                }

                $resultado[] = [
                    'id' => $aula->id,
                    'numero_aula' => $aula->numero_aula,
                    'capacidad' => $aula->capacidad,
                    'bloques_disponibles' => $bloques_disponibles
                ];
            }

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => auth()->id(),
                'ip_address' => \App\Helpers\IpHelper::getClientIp(),
                'tabla' => 'aulas',
                'operacion' => 'exportacion',
                'descripcion' => "Consultó reporte de aulas disponibles" . ($dia ? " (Día: $dia)" : "")
            ]);

            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar Aulas Disponibles a PDF
     */
    public function aulasDisponiblesPDF(Request $request)
    {
        try {
            $dia = $request->query('dia');
            $aula_id = $request->query('aula_id');
            $bloque_id = $request->query('bloque_id');

            $aulasQuery = Aulas::where('activo', true);
            if ($aula_id) {
                $aulasQuery->where('id', $aula_id);
            }
            $aulas = $aulasQuery->get();

            $bloquesQuery = BloqueHorario::orderBy('numero_bloque');
            if ($bloque_id) {
                $bloquesQuery->where('id', $bloque_id);
            }
            $bloques = $bloquesQuery->get();

            // Obtener todos los horarios ocupados de una sola vez
            $horariosQuery = Horario::select('id_aula', 'id_bloque', 'dias_semana');

            if ($dia) {
                $horariosQuery->whereJsonContains('dias_semana', $dia);
            }

            $horariosOcupados = $horariosQuery->get();

            // Crear un set de combinaciones aula-bloque ocupadas
            $ocupados = [];
            foreach ($horariosOcupados as $horario) {
                $key = $horario->id_aula . '-' . $horario->id_bloque;
                $ocupados[$key] = true;
            }

            $resultado = [];

            foreach ($aulas as $aula) {
                $bloques_info = [];

                foreach ($bloques as $bloque) {
                    $key = $aula->id . '-' . $bloque->id;
                    $ocupada = isset($ocupados[$key]);

                    $bloques_info[] = [
                        'bloque' => $bloque->nombre,
                        'hora_inicio' => date('H:i', strtotime($bloque->hora_inicio)),
                        'hora_fin' => date('H:i', strtotime($bloque->hora_fin)),
                        'disponible' => !$ocupada
                    ];
                }

                $resultado[] = [
                    'aula' => $aula,
                    'bloques' => $bloques_info
                ];
            }

            $data = [
                'aulas' => $resultado,
                'dia' => $dia,
                'bloques' => $bloques,
                'fecha_generacion' => now()->format('d/m/Y H:i')
            ];

            $pdf = Pdf::loadView('reportes.aulas-disponibles', $data);

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => auth()->id(),
                'ip_address' => \App\Helpers\IpHelper::getClientIp(),
                'tabla' => 'aulas',
                'operacion' => 'exportacion',
                'descripcion' => "Exportó reporte de aulas disponibles a PDF"
            ]);

            return $pdf->download('aulas_disponibles_' . time() . '.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar Aulas Disponibles a Excel
     */
    public function aulasDisponiblesExcel(Request $request)
    {
        try {
            $dia = $request->query('dia');
            $aula_id = $request->query('aula_id');
            $bloque_id = $request->query('bloque_id');

            $aulasQuery = Aulas::where('activo', true);
            if ($aula_id) {
                $aulasQuery->where('id', $aula_id);
            }
            $aulas = $aulasQuery->get();

            $bloquesQuery = BloqueHorario::orderBy('numero_bloque');
            if ($bloque_id) {
                $bloquesQuery->where('id', $bloque_id);
            }
            $bloques = $bloquesQuery->get();

            // Obtener todos los horarios ocupados de una sola vez
            $horariosQuery = Horario::select('id_aula', 'id_bloque', 'dias_semana');

            if ($dia) {
                $horariosQuery->whereJsonContains('dias_semana', $dia);
            }

            $horariosOcupados = $horariosQuery->get();

            // Crear un set de combinaciones aula-bloque ocupadas
            $ocupados = [];
            foreach ($horariosOcupados as $horario) {
                $key = $horario->id_aula . '-' . $horario->id_bloque;
                $ocupados[$key] = true;
            }

            $filename = 'aulas_disponibles_' . time() . '.csv';
            $handle = fopen('php://temp', 'r+');

            // Encabezados
            $encabezados = ['Aula', 'Capacidad'];
            foreach ($bloques as $bloque) {
                $horaInicio = date('H:i', strtotime($bloque->hora_inicio));
                $horaFin = date('H:i', strtotime($bloque->hora_fin));
                $encabezados[] = $bloque->nombre . ' (' . $horaInicio . '-' . $horaFin . ')';
            }
            fputcsv($handle, $encabezados);

            // Datos
            foreach ($aulas as $aula) {
                $fila = [$aula->numero_aula, $aula->capacidad];

                foreach ($bloques as $bloque) {
                    $key = $aula->id . '-' . $bloque->id;
                    $ocupada = isset($ocupados[$key]);
                    $fila[] = $ocupada ? 'Ocupada' : 'Libre';
                }

                fputcsv($handle, $fila);
            }

            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => auth()->id(),
                'ip_address' => \App\Helpers\IpHelper::getClientIp(),
                'tabla' => 'aulas',
                'operacion' => 'exportacion',
                'descripcion' => "Exportó reporte de aulas disponibles a Excel/CSV"
            ]);

            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
