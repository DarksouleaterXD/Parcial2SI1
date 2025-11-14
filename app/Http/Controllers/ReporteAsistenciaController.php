<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Sesion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteAsistenciaController extends Controller
{
    /**
     * Reporte general de asistencias
     * GET /api/reportes/asistencias
     */
    public function reporteGeneral(Request $request)
    {
        try {
            $query = Asistencia::with([
                'sesion.grupo.materia',
                'sesion.grupo.carrera',
                'docente.persona',
                'sesion.aula'
            ]);

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

            if ($request->has('docente_id')) {
                $query->where('docente_id', $request->docente_id);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('validado')) {
                $query->where('validado', $request->validado === 'true' || $request->validado === '1');
            }

            $asistencias = $query->orderBy('marcado_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $asistencias->items(),
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
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de asistencias por docente
     * GET /api/reportes/asistencias/estadisticas-docente
     */
    public function estadisticasPorDocente(Request $request)
    {
        try {
            $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->toDateString());
            $fechaHasta = $request->input('fecha_hasta', Carbon::now()->endOfMonth()->toDateString());

            $estadisticas = Docente::with('persona')
                ->withCount([
                    'asistencias as total_asistencias' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                            $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                        });
                    },
                    'asistencias as asistencias_presente' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'presente')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                    'asistencias as asistencias_retardo' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'retardo')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                    'asistencias as asistencias_ausente' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'ausente')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                    'asistencias as asistencias_justificado' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'justificado')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                    'asistencias as pendientes_validacion' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('validado', false)
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    }
                ])
                ->where('activo', true)
                ->get()
                ->map(function ($docente) {
                    $total = $docente->total_asistencias;
                    $porcentajePresente = $total > 0 ? round(($docente->asistencias_presente / $total) * 100, 2) : 0;

                    return [
                        'docente_id' => $docente->id,
                        'nombre_completo' => $docente->persona->nombre_completo ?? 'N/A',
                        'total_asistencias' => $total,
                        'presente' => $docente->asistencias_presente,
                        'retardo' => $docente->asistencias_retardo,
                        'ausente' => $docente->asistencias_ausente,
                        'justificado' => $docente->asistencias_justificado,
                        'pendientes_validacion' => $docente->pendientes_validacion,
                        'porcentaje_presente' => $porcentajePresente,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'periodo' => [
                    'fecha_desde' => $fechaDesde,
                    'fecha_hasta' => $fechaHasta,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resumen de asistencias pendientes de validación
     * GET /api/reportes/asistencias/pendientes
     */
    public function asistenciasPendientes()
    {
        try {
            $pendientes = Asistencia::with([
                'sesion.grupo.materia',
                'docente.persona',
                'sesion'
            ])
            ->where('validado', false)
            ->orderBy('marcado_at', 'desc')
            ->get()
            ->map(function ($asistencia) {
                return [
                    'id' => $asistencia->id,
                    'docente' => $asistencia->docente->persona->nombre_completo ?? 'N/A',
                    'materia' => $asistencia->sesion->grupo->materia->nombre ?? 'N/A',
                    'fecha' => $asistencia->sesion->fecha,
                    'hora_marcado' => $asistencia->hora_marcado,
                    'estado' => $asistencia->estado,
                    'metodo_registro' => $asistencia->metodo_registro,
                    'dias_pendiente' => Carbon::parse($asistencia->marcado_at)->diffInDays(Carbon::now()),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $pendientes,
                'total' => $pendientes->count()
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
     * Resumen general (Dashboard)
     * GET /api/reportes/asistencias/resumen
     */
    public function resumenGeneral(Request $request)
    {
        try {
            $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->toDateString());
            $fechaHasta = $request->input('fecha_hasta', Carbon::now()->toDateString());

            // Total de asistencias por estado
            $porEstado = Asistencia::select('estado', DB::raw('count(*) as total'))
                ->whereHas('sesion', function ($q) use ($fechaDesde, $fechaHasta) {
                    $q->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                })
                ->groupBy('estado')
                ->get()
                ->pluck('total', 'estado');

            // Pendientes de validación
            $pendientesValidacion = Asistencia::where('validado', false)
                ->whereHas('sesion', function ($q) use ($fechaDesde, $fechaHasta) {
                    $q->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                })
                ->count();

            // Total de sesiones programadas
            $sesionesTotal = Sesion::whereBetween('fecha', [$fechaDesde, $fechaHasta])->count();

            // Sesiones con asistencia registrada
            $sesionesConAsistencia = Sesion::whereBetween('fecha', [$fechaDesde, $fechaHasta])
                ->has('asistencias')
                ->count();

            // Porcentaje de cumplimiento
            $porcentajeCumplimiento = $sesionesTotal > 0
                ? round(($sesionesConAsistencia / $sesionesTotal) * 100, 2)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'por_estado' => [
                        'presente' => $porEstado['presente'] ?? 0,
                        'retardo' => $porEstado['retardo'] ?? 0,
                        'ausente' => $porEstado['ausente'] ?? 0,
                        'justificado' => $porEstado['justificado'] ?? 0,
                    ],
                    'pendientes_validacion' => $pendientesValidacion,
                    'sesiones_total' => $sesionesTotal,
                    'sesiones_con_asistencia' => $sesionesConAsistencia,
                    'porcentaje_cumplimiento' => $porcentajeCumplimiento,
                ],
                'periodo' => [
                    'fecha_desde' => $fechaDesde,
                    'fecha_hasta' => $fechaHasta,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar resumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar reporte de asistencias a PDF
     * GET /api/reportes/asistencias/pdf
     */
    public function exportarPDF(Request $request)
    {
        try {
            $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->toDateString());
            $fechaHasta = $request->input('fecha_hasta', Carbon::now()->toDateString());

            $estadisticas = Docente::with('persona')
                ->withCount([
                    'asistencias as total_asistencias' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                            $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                        });
                    },
                    'asistencias as asistencias_presente' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'presente')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                    'asistencias as asistencias_retardo' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'retardo')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                    'asistencias as asistencias_ausente' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'ausente')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                ])
                ->where('activo', true)
                ->get();

            $pdf = \PDF::loadView('reportes.asistencias', [
                'estadisticas' => $estadisticas,
                'fechaDesde' => $fechaDesde,
                'fechaHasta' => $fechaHasta,
            ]);

            return $pdf->download('reporte_asistencias_' . date('Y-m-d') . '.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar reporte de asistencias a Excel
     * GET /api/reportes/asistencias/excel
     */
    public function exportarExcel(Request $request)
    {
        try {
            $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->toDateString());
            $fechaHasta = $request->input('fecha_hasta', Carbon::now()->toDateString());

            $estadisticas = Docente::with('persona')
                ->withCount([
                    'asistencias as total_asistencias' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                            $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                        });
                    },
                    'asistencias as asistencias_presente' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'presente')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                    'asistencias as asistencias_retardo' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'retardo')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                    'asistencias as asistencias_ausente' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'ausente')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                    'asistencias as asistencias_justificado' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('estado', 'justificado')
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                    'asistencias as pendientes_validacion' => function ($q) use ($fechaDesde, $fechaHasta) {
                        $q->where('validado', false)
                          ->whereHas('sesion', function ($sq) use ($fechaDesde, $fechaHasta) {
                              $sq->whereBetween('fecha', [$fechaDesde, $fechaHasta]);
                          });
                    },
                ])
                ->where('activo', true)
                ->get();

            // Preparar datos para Excel
            $data = $estadisticas->map(function ($docente) {
                $totalAsistencias = $docente->total_asistencias ?? 0;
                $presente = $docente->asistencias_presente ?? 0;
                $porcentaje = $totalAsistencias > 0 ? round(($presente / $totalAsistencias) * 100, 2) : 0;

                return [
                    'Docente' => $docente->persona->nombre_completo ?? 'N/A',
                    'Total Asistencias' => $totalAsistencias,
                    'Presente' => $presente,
                    'Retardo' => $docente->asistencias_retardo ?? 0,
                    'Ausente' => $docente->asistencias_ausente ?? 0,
                    'Justificado' => $docente->asistencias_justificado ?? 0,
                    'Pendientes' => $docente->pendientes_validacion ?? 0,
                    '% Presente' => $porcentaje,
                ];
            });

            // Usar SimpleExcel o crear CSV manualmente
            $filename = 'reporte_asistencias_' . date('Y-m-d') . '.csv';
            $handle = fopen('php://temp', 'r+');

            // Encabezados
            fputcsv($handle, array_keys($data->first()));

            // Datos
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }

            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
