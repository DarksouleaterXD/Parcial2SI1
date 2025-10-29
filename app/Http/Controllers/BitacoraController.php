<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BitacoraController extends Controller
{
    /**
     * Display a listing of bitacoras with filters
     * GET /api/bitacoras
     */
    public function index(Request $request)
    {
        try {
            $query = Bitacora::with('usuario:id,nombre,email')
                ->orderBy('created_at', 'desc');

            // Filtro por usuario
            if ($request->has('id_usuario') && $request->id_usuario) {
                $query->where('id_usuario', $request->id_usuario);
            }

            // Filtro por tabla
            if ($request->has('tabla') && $request->tabla) {
                $query->where('tabla', $request->tabla);
            }

            // Filtro por operación
            if ($request->has('operacion') && $request->operacion) {
                $query->where('operacion', $request->operacion);
            }

            // Filtro por rango de fechas
            if ($request->has('fecha_inicio') && $request->fecha_inicio) {
                $query->whereDate('created_at', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin') && $request->fecha_fin) {
                $query->whereDate('created_at', '<=', $request->fecha_fin);
            }

            // Búsqueda por descripción
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('descripcion', 'LIKE', "%{$search}%")
                    ->orWhere('tabla', 'LIKE', "%{$search}%");
            }

            // Paginación
            $perPage = $request->input('per_page', 20);
            $bitacoras = $query->paginate($perPage);

            return response()->json($bitacoras);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener bitácoras',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bitácora statistics
     * GET /api/bitacoras/estadisticas/resumen
     */
    public function estadisticas(Request $request)
    {
        try {
            $query = Bitacora::query();

            // Filtrar por rango de fechas si se especifica
            if ($request->has('fecha_inicio') && $request->fecha_inicio) {
                $query->whereDate('created_at', '>=', $request->fecha_inicio);
            }
            if ($request->has('fecha_fin') && $request->fecha_fin) {
                $query->whereDate('created_at', '<=', $request->fecha_fin);
            }

            // Estadísticas por operación
            $operaciones = DB::table('bitacoras')
                ->select('operacion', DB::raw('COUNT(*) as cantidad'))
                ->groupBy('operacion')
                ->get();

            // Estadísticas por tabla
            $tablas = DB::table('bitacoras')
                ->select('tabla', DB::raw('COUNT(*) as cantidad'))
                ->groupBy('tabla')
                ->orderBy('cantidad', 'desc')
                ->get();

            // Estadísticas por usuario (Top 10)
            $usuarios = DB::table('bitacoras')
                ->select('id_usuario', DB::raw('COUNT(*) as cantidad'))
                ->join('users', 'bitacoras.id_usuario', '=', 'users.id')
                ->selectRaw('users.nombre, users.email')
                ->groupBy('id_usuario', 'users.nombre', 'users.email')
                ->orderBy('cantidad', 'desc')
                ->limit(10)
                ->get();

            // Total de registros
            $total = $query->count();

            // Últimas 24 horas
            $ultimas24h = DB::table('bitacoras')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_registros' => $total,
                    'ultimas_24h' => $ultimas24h,
                    'por_operacion' => $operaciones,
                    'por_tabla' => $tablas,
                    'por_usuario' => $usuarios,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific bitácora entry
     * GET /api/bitacoras/{id}
     */
    public function show(Bitacora $bitacora)
    {
        try {
            $bitacora->load('usuario:id,nombre,email,rol');

            return response()->json([
                'success' => true,
                'data' => $bitacora
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener bitácora',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a bitácora entry
     * POST /api/bitacoras
     */
    public static function registrar($id_usuario, $tabla, $operacion, $id_registro, $descripcion)
    {
        try {
            Bitacora::create([
                'id_usuario' => $id_usuario,
                'tabla' => $tabla,
                'operacion' => $operacion,
                'id_registro' => $id_registro,
                'descripcion' => $descripcion,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al registrar bitácora: ' . $e->getMessage());
        }
    }

    /**
     * Get bitácora entries por tabla
     * GET /api/bitacoras/tabla/{tabla}
     */
    public function porTabla($tabla, Request $request)
    {
        try {
            $query = Bitacora::with('usuario:id,nombre,email')
                ->where('tabla', $tabla)
                ->orderBy('created_at', 'desc');

            // Filtros adicionales
            if ($request->has('id_registro') && $request->id_registro) {
                $query->where('id_registro', $request->id_registro);
            }

            if ($request->has('operacion') && $request->operacion) {
                $query->where('operacion', $request->operacion);
            }

            $perPage = $request->input('per_page', 20);
            $bitacoras = $query->paginate($perPage);

            return response()->json($bitacoras);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener bitácoras',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bitácora entries por usuario
     * GET /api/bitacoras/usuario/{id_usuario}
     */
    public function porUsuario($id_usuario, Request $request)
    {
        try {
            $query = Bitacora::with('usuario:id,nombre,email')
                ->where('id_usuario', $id_usuario)
                ->orderBy('created_at', 'desc');

            // Filtros adicionales
            if ($request->has('tabla') && $request->tabla) {
                $query->where('tabla', $request->tabla);
            }

            if ($request->has('operacion') && $request->operacion) {
                $query->where('operacion', $request->operacion);
            }

            $perPage = $request->input('per_page', 20);
            $bitacoras = $query->paginate($perPage);

            return response()->json($bitacoras);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener bitácoras',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export bitácora to CSV
     * GET /api/bitacoras/exportar/csv
     */
    public function exportarCSV(Request $request)
    {
        try {
            $query = Bitacora::with('usuario:id,nombre,email')
                ->orderBy('created_at', 'desc');

            // Aplicar filtros
            if ($request->has('tabla') && $request->tabla) {
                $query->where('tabla', $request->tabla);
            }
            if ($request->has('operacion') && $request->operacion) {
                $query->where('operacion', $request->operacion);
            }
            if ($request->has('fecha_inicio') && $request->fecha_inicio) {
                $query->whereDate('created_at', '>=', $request->fecha_inicio);
            }
            if ($request->has('fecha_fin') && $request->fecha_fin) {
                $query->whereDate('created_at', '<=', $request->fecha_fin);
            }

            $bitacoras = $query->get();

            $csv = "ID,Usuario,Email,Tabla,Operación,ID Registro,Descripción,Fecha\n";
            foreach ($bitacoras as $b) {
                $csv .= "{$b->id},\"{$b->usuario->nombre}\",\"{$b->usuario->email}\",{$b->tabla},{$b->operacion},{$b->id_registro},\"{$b->descripcion}\",{$b->created_at}\n";
            }

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="bitacora_' . now()->format('YmdHis') . '.csv"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar bitácora',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create sample data for testing
     * GET /api/bitacoras/seed/datos-prueba
     */
    public function seedDatos()
    {
        try {
            $users = User::limit(3)->get();
            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay usuarios disponibles para crear datos de prueba'
                ], 400);
            }

            $tablas = ['carreras', 'materias', 'docentes', 'grupos', 'aulas', 'horarios', 'usuarios', 'periodos'];
            $operaciones = ['CREATE', 'UPDATE', 'DELETE', 'READ'];

            for ($i = 0; $i < 50; $i++) {
                Bitacora::create([
                    'id_usuario' => $users->random()->id,
                    'tabla' => $tablas[array_rand($tablas)],
                    'operacion' => $operaciones[array_rand($operaciones)],
                    'id_registro' => rand(1, 100),
                    'descripcion' => 'Operación de prueba #' . ($i + 1),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => '50 registros de prueba creados exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear datos de prueba',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
