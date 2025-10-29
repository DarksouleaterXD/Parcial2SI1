<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Http\Request;

class PeriodoController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/periodos?search=2025&activo=1&vigente=1
     */
    public function index(Request $request)
    {
        try {
            $query = Periodo::orderBy('fecha_inicio', 'desc');

            // Filtro por búsqueda
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('nombre', 'ilike', "%{$search}%");
            }

            // Filtro por estado activo
            if ($request->has('activo') && $request->activo !== null) {
                $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
            }

            // Filtro por vigente
            if ($request->has('vigente') && $request->vigente !== null) {
                $query->where('vigente', filter_var($request->vigente, FILTER_VALIDATE_BOOLEAN));
            }

            // Paginación
            $periodos = $query->paginate(
                $request->per_page ?? 15,
                ['*'],
                'page',
                $request->page ?? 1
            );

            return response()->json([
                'success' => true,
                'data' => $periodos->items(),
                'pagination' => [
                    'total' => $periodos->total(),
                    'count' => $periodos->count(),
                    'per_page' => $periodos->perPage(),
                    'current_page' => $periodos->currentPage(),
                    'total_pages' => $periodos->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar periodos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/periodos
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => ['required', 'string', 'max:50', 'unique:periodos,nombre'],
                'fecha_inicio' => ['required', 'date'],
                'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
                'activo' => ['sometimes', 'boolean'],
                'vigente' => ['sometimes', 'boolean'],
            ]);

            // Validar solapamiento de fechas
            $solapamiento = Periodo::where(function ($query) use ($validated) {
                $query->whereBetween('fecha_inicio', [$validated['fecha_inicio'], $validated['fecha_fin']])
                    ->orWhereBetween('fecha_fin', [$validated['fecha_inicio'], $validated['fecha_fin']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('fecha_inicio', '<=', $validated['fecha_inicio'])
                          ->where('fecha_fin', '>=', $validated['fecha_fin']);
                    });
            })->exists();

            if ($solapamiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las fechas del periodo se solapan con otro periodo existente'
                ], 422);
            }

            // Si se marca como vigente, desmarcar otros
            if (isset($validated['vigente']) && $validated['vigente']) {
                Periodo::where('vigente', true)->update(['vigente' => false]);
            }

            $periodo = Periodo::create($validated);

            // Registrar en bitácora
            $user = auth()->user();
            Bitacora::create([
                'id_usuario' => $user->id,
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'periodos',
                'operacion' => 'crear',
                'id_registro' => $periodo->id,
                'descripcion' => "Se creó el período {$periodo->nombre} ({$periodo->fecha_inicio->format('d/m/Y')} - {$periodo->fecha_fin->format('d/m/Y')})",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Período creado exitosamente',
                'data' => $periodo
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear período: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/periodos/{id}
     */
    public function show(Periodo $periodo)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $periodo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener período: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/periodos/{id}
     */
    public function update(Request $request, Periodo $periodo)
    {
        try {
            $validated = $request->validate([
                'nombre' => ['sometimes', 'string', 'max:50', 'unique:periodos,nombre,' . $periodo->id],
                'fecha_inicio' => ['sometimes', 'date'],
                'fecha_fin' => ['sometimes', 'date'],
                'activo' => ['sometimes', 'boolean'],
                'vigente' => ['sometimes', 'boolean'],
            ]);

            // Validar solapamiento si se cambian fechas
            if (isset($validated['fecha_inicio']) || isset($validated['fecha_fin'])) {
                $fecha_inicio = $validated['fecha_inicio'] ?? $periodo->fecha_inicio;
                $fecha_fin = $validated['fecha_fin'] ?? $periodo->fecha_fin;

                $solapamiento = Periodo::where('id', '!=', $periodo->id)
                    ->where(function ($query) use ($fecha_inicio, $fecha_fin) {
                        $query->whereBetween('fecha_inicio', [$fecha_inicio, $fecha_fin])
                            ->orWhereBetween('fecha_fin', [$fecha_inicio, $fecha_fin])
                            ->orWhere(function ($q) use ($fecha_inicio, $fecha_fin) {
                                $q->where('fecha_inicio', '<=', $fecha_inicio)
                                  ->where('fecha_fin', '>=', $fecha_fin);
                            });
                    })->exists();

                if ($solapamiento) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Las fechas del periodo se solapan con otro periodo existente'
                    ], 422);
                }
            }

            // Si se marca como vigente, desmarcar otros
            if (isset($validated['vigente']) && $validated['vigente'] && !$periodo->vigente) {
                Periodo::where('vigente', true)->where('id', '!=', $periodo->id)->update(['vigente' => false]);
            }

            $periodo->update($validated);

            // Registrar en bitácora
            $user = auth()->user();
            Bitacora::create([
                'id_usuario' => $user->id,
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'periodos',
                'operacion' => 'editar',
                'id_registro' => $periodo->id,
                'descripcion' => "Se actualizó el período {$periodo->nombre}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Período actualizado exitosamente',
                'data' => $periodo
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar período: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/periodos/{id}
     */
    public function destroy(Periodo $periodo)
    {
        try {
            // No permitir eliminar periodo vigente
            if ($periodo->vigente) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un periodo vigente'
                ], 422);
            }

            $periodo->delete();

            // Registrar en bitácora
            $user = auth()->user();
            Bitacora::create([
                'id_usuario' => $user->id,
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'periodos',
                'operacion' => 'eliminar',
                'id_registro' => $periodo->id,
                'descripcion' => "Se eliminó el período {$periodo->nombre}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Período eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar período: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar periodo como vigente (disponible)
     * PATCH /api/periodos/{id}/vigente
     */
    public function marcarVigente(Periodo $periodo)
    {
        try {
            // Desmarcar otros periodos como vigentes
            Periodo::where('vigente', true)->where('id', '!=', $periodo->id)->update(['vigente' => false]);

            $periodo->update(['vigente' => true]);

            // Registrar en bitácora
            $user = auth()->user();
            Bitacora::create([
                'id_usuario' => $user->id,
                'ip_address' => IpHelper::getClientIp(),
                'tabla' => 'periodos',
                'operacion' => 'cambiar_estado',
                'id_registro' => $periodo->id,
                'descripcion' => "Se marcó el período {$periodo->nombre} como vigente",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Período marcado como vigente',
                'data' => $periodo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar período como vigente: ' . $e->getMessage()
            ], 500);
        }
    }
}
