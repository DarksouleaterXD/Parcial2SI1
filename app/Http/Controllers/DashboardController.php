<?php

namespace App\Http\Controllers;

use App\Models\Aulas;
use App\Models\Horario;
use App\Models\Asistencia;
use App\Models\CargaHoraria;
use App\Models\Periodo;
use App\Models\Grupo;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Obtener KPIs del Dashboard
     * GET /api/dashboard/kpis
     */
    public function kpis(Request $request)
    {
        try {
            $periodo_id = $request->query('periodo_id');
            $carrera_id = $request->query('carrera_id');

            // KPI 1: Ocupación de Aulas
            $ocupacionAulas = $this->calcularOcupacionAulas($periodo_id, $carrera_id);

            // KPI 2: Carga Horaria por Docente
            $cargaHoraria = $this->calcularCargaHoraria($periodo_id, $carrera_id);

            // KPI 3: Asistencia Promedio
            $asistenciaPromedio = $this->calcularAsistenciaPromedio($periodo_id, $carrera_id);

            // KPI 4: Aulas por Tipo
            $aulasPorTipo = $this->calcularAulasPorTipo($periodo_id);

            // KPI 5: Grupos Activos
            $gruposActivos = $this->calcularGruposActivos($periodo_id, $carrera_id);

            // KPI 6: Horas Docente Promedio
            $horasPromedioDocente = $this->calcularHorasPromedioDocente($periodo_id, $carrera_id);

            return response()->json([
                'success' => true,
                'message' => 'KPIs obtenidos exitosamente',
                'data' => [
                    'ocupacion_aulas' => $ocupacionAulas,
                    'carga_horaria' => $cargaHoraria,
                    'asistencia_promedio' => $asistenciaPromedio,
                    'aulas_por_tipo' => $aulasPorTipo,
                    'grupos_activos' => $gruposActivos,
                    'horas_promedio_docente' => $horasPromedioDocente,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener KPIs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos para gráficos
     * GET /api/dashboard/graficos
     */
    public function graficos(Request $request)
    {
        try {
            $periodo_id = $request->query('periodo_id');
            $carrera_id = $request->query('carrera_id');
            $tipo = $request->query('tipo', 'ocupacion'); // ocupacion, asistencia, carga

            $datos = [];

            if ($tipo === 'ocupacion' || $tipo === 'todos') {
                $datos['ocupacion_por_dia'] = $this->ocupacionPorDia($periodo_id, $carrera_id);
                $datos['ocupacion_por_bloque'] = $this->ocupacionPorBloque($periodo_id, $carrera_id);
            }

            if ($tipo === 'asistencia' || $tipo === 'todos') {
                $datos['asistencia_por_grupo'] = $this->asistenciaPorGrupo($periodo_id, $carrera_id);
                $datos['asistencia_por_semana'] = $this->asistenciaPorSemana($periodo_id, $carrera_id);
            }

            if ($tipo === 'carga' || $tipo === 'todos') {
                $datos['carga_por_docente'] = $this->cargaPorDocente($periodo_id, $carrera_id);
                $datos['carga_por_carrera'] = $this->cargaPorCarrera($periodo_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Datos de gráficos obtenidos exitosamente',
                'data' => $datos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de gráficos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener catálogos para filtros
     * GET /api/dashboard/catalogos
     */
    public function catalogos()
    {
        try {
            $periodos = Periodo::select('id', 'nombre', 'vigente')->get();
            $carreras = Carrera::select('id', 'nombre', 'sigla')->where('activo', true)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'periodos' => $periodos,
                    'carreras' => $carreras,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener catálogos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ======================== MÉTODOS PRIVADOS PARA KPIs ========================

    private function calcularOcupacionAulas($periodo_id = null, $carrera_id = null)
    {
        $query = Horario::query();

        if ($periodo_id) {
            $query->where('id_periodo', $periodo_id);
        }

        // Si hay carrera, filtrar por grupos de esa carrera
        if ($carrera_id) {
            $query->whereIn('id_grupo', function ($q) use ($carrera_id) {
                $q->select('id')->from('grupos')->where('id_carrera', $carrera_id);
            });
        }

        $totalHorarios = $query->count();
        $aulasOcupadas = $query->distinct('id_aula')->count();

        $totalAulas = Aulas::where('activo', true)->count();

        return [
            'total_aulas' => $totalAulas,
            'aulas_ocupadas' => $aulasOcupadas,
            'tasa_ocupacion' => $totalAulas > 0 ? round(($aulasOcupadas / $totalAulas) * 100, 2) : 0,
            'total_horarios' => $totalHorarios,
        ];
    }

    private function calcularCargaHoraria($periodo_id = null, $carrera_id = null)
    {
        $query = CargaHoraria::query();

        if ($periodo_id) {
            $query->where('id_periodo', $periodo_id);
        }

        if ($carrera_id) {
            $query->whereIn('id_grupo', function ($q) use ($carrera_id) {
                $q->select('id')->from('grupos')->where('id_carrera', $carrera_id);
            });
        }

        $totalHoras = $query->sum('horas_semana');
        $promedioHoras = $query->avg('horas_semana');

        return [
            'total_horas_semana' => $totalHoras ?? 0,
            'promedio_horas_docente' => round($promedioHoras ?? 0, 2),
            'total_asignaciones' => $query->count(),
        ];
    }

    private function calcularAsistenciaPromedio($periodo_id = null, $carrera_id = null)
    {
        $query = Asistencia::query();

        if ($periodo_id) {
            $query->whereIn('id_grupo', function ($q) use ($periodo_id) {
                $q->select('id')->from('grupos')->whereIn('id_periodo', [$periodo_id]);
            });
        }

        if ($carrera_id) {
            $query->whereIn('id_grupo', function ($q) use ($carrera_id) {
                $q->select('id')->from('grupos')->where('id_carrera', $carrera_id);
            });
        }

        $total = $query->count();
        $asistentes = $query->where('presente', true)->count();

        return [
            'total_registros' => $total,
            'asistentes' => $asistentes,
            'tasa_asistencia' => $total > 0 ? round(($asistentes / $total) * 100, 2) : 0,
        ];
    }

    private function calcularAulasPorTipo($periodo_id = null)
    {
        $tipos = ['teorica', 'practica', 'laboratorio', 'mixta'];
        $resultado = [];

        foreach ($tipos as $tipo) {
            $query = Aulas::where('tipo', $tipo)->where('activo', true);
            $resultado[] = [
                'tipo' => $tipo,
                'cantidad' => $query->count(),
                'capacidad_total' => $query->sum('capacidad'),
            ];
        }

        return $resultado;
    }

    private function calcularGruposActivos($periodo_id = null, $carrera_id = null)
    {
        $query = Grupo::query();

        if ($periodo_id) {
            $query->where('id_periodo', $periodo_id);
        }

        if ($carrera_id) {
            $query->where('id_carrera', $carrera_id);
        }

        return [
            'grupos_activos' => $query->count(),
            'estudiantes_total' => DB::table('grupo_estudiante')
                ->whereIn('id_grupo', $query->select('id'))
                ->distinct('id_estudiante')
                ->count(),
        ];
    }

    private function calcularHorasPromedioDocente($periodo_id = null, $carrera_id = null)
    {
        $query = CargaHoraria::query();

        if ($periodo_id) {
            $query->where('id_periodo', $periodo_id);
        }

        if ($carrera_id) {
            $query->whereIn('id_grupo', function ($q) use ($carrera_id) {
                $q->select('id')->from('grupos')->where('id_carrera', $carrera_id);
            });
        }

        $promedio = $query->avg('horas_semana');
        $maximo = $query->max('horas_semana');
        $minimo = $query->min('horas_semana');

        return [
            'promedio' => round($promedio ?? 0, 2),
            'maximo' => $maximo ?? 0,
            'minimo' => $minimo ?? 0,
        ];
    }

    // ======================== MÉTODOS PRIVADOS PARA GRÁFICOS ========================

    private function ocupacionPorDia($periodo_id = null, $carrera_id = null)
    {
        $query = Horario::select('dia_semana', DB::raw('COUNT(*) as total'))
            ->groupBy('dia_semana');

        if ($periodo_id) {
            $query->where('id_periodo', $periodo_id);
        }

        if ($carrera_id) {
            $query->whereIn('id_grupo', function ($q) use ($carrera_id) {
                $q->select('id')->from('grupos')->where('id_carrera', $carrera_id);
            });
        }

        $datos = $query->get();
        $dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

        $resultado = [];
        for ($i = 1; $i <= 5; $i++) {
            $dato = $datos->where('dia_semana', $i)->first();
            $resultado[] = [
                'dia' => $dias[$i],
                'cantidad' => $dato->total ?? 0,
            ];
        }

        return $resultado;
    }

    private function ocupacionPorBloque($periodo_id = null, $carrera_id = null)
    {
        $query = Horario::select('id_bloque', DB::raw('COUNT(*) as total'))
            ->groupBy('id_bloque')
            ->with('bloque:id,nombre');

        if ($periodo_id) {
            $query->where('id_periodo', $periodo_id);
        }

        if ($carrera_id) {
            $query->whereIn('id_grupo', function ($q) use ($carrera_id) {
                $q->select('id')->from('grupos')->where('id_carrera', $carrera_id);
            });
        }

        return $query->get()->map(function ($item) {
            return [
                'bloque' => $item->bloque->nombre ?? 'Desconocido',
                'cantidad' => $item->total,
            ];
        })->values();
    }

    private function asistenciaPorGrupo($periodo_id = null, $carrera_id = null)
    {
        $query = Asistencia::select('id_grupo', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN presente = true THEN 1 ELSE 0 END) as asistentes'))
            ->groupBy('id_grupo')
            ->with('grupo:id,nombre');

        if ($carrera_id) {
            $query->whereIn('id_grupo', function ($q) use ($carrera_id) {
                $q->select('id')->from('grupos')->where('id_carrera', $carrera_id);
            });
        }

        return $query->get()->map(function ($item) {
            $tasa = $item->total > 0 ? round(($item->asistentes / $item->total) * 100, 2) : 0;
            return [
                'grupo' => $item->grupo->nombre ?? 'Desconocido',
                'tasa_asistencia' => $tasa,
            ];
        })->values();
    }

    private function asistenciaPorSemana($periodo_id = null, $carrera_id = null)
    {
        $query = Asistencia::select(DB::raw('WEEK(fecha) as semana'), DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN presente = true THEN 1 ELSE 0 END) as asistentes'))
            ->groupBy(DB::raw('WEEK(fecha)'));

        if ($periodo_id) {
            $query->whereIn('id_grupo', function ($q) use ($periodo_id) {
                $q->select('id')->from('grupos')->where('id_periodo', $periodo_id);
            });
        }

        if ($carrera_id) {
            $query->whereIn('id_grupo', function ($q) use ($carrera_id) {
                $q->select('id')->from('grupos')->where('id_carrera', $carrera_id);
            });
        }

        return $query->orderBy('semana')->get()->map(function ($item) {
            $tasa = $item->total > 0 ? round(($item->asistentes / $item->total) * 100, 2) : 0;
            return [
                'semana' => 'Semana ' . $item->semana,
                'tasa_asistencia' => $tasa,
            ];
        })->values();
    }

    private function cargaPorDocente($periodo_id = null, $carrera_id = null)
    {
        $query = CargaHoraria::select('id_docente', DB::raw('SUM(horas_semana) as total_horas'))
            ->groupBy('id_docente')
            ->with('docente:id,nombre')
            ->limit(10);

        if ($periodo_id) {
            $query->where('id_periodo', $periodo_id);
        }

        if ($carrera_id) {
            $query->whereIn('id_grupo', function ($q) use ($carrera_id) {
                $q->select('id')->from('grupos')->where('id_carrera', $carrera_id);
            });
        }

        return $query->get()->map(function ($item) {
            return [
                'docente' => $item->docente->nombre ?? 'Desconocido',
                'horas' => $item->total_horas,
            ];
        })->values();
    }

    private function cargaPorCarrera($periodo_id = null)
    {
        $query = DB::table('carga_horaria')
            ->select('grupos.id_carrera', 'carreras.nombre', DB::raw('SUM(carga_horaria.horas_semana) as total_horas'))
            ->join('grupos', 'carga_horaria.id_grupo', '=', 'grupos.id')
            ->join('carreras', 'grupos.id_carrera', '=', 'carreras.id')
            ->groupBy('grupos.id_carrera', 'carreras.nombre');

        if ($periodo_id) {
            $query->where('carga_horaria.id_periodo', $periodo_id);
        }

        return $query->get()->map(function ($item) {
            return [
                'carrera' => $item->nombre,
                'horas' => $item->total_horas,
            ];
        })->values();
    }
}
