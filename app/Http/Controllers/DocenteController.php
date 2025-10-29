<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Persona;
use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocenteController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/docentes
     */
    public function index(Request $request)
    {
        $per_page = $request->query('per_page', 15);
        $search = $request->query('search', '');

        $query = Docente::with('persona', 'usuario');

        if ($search) {
            $query->whereHas('persona', function ($q) use ($search) {
                $q->where('ci', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('correo', 'like', "%{$search}%");
            });
        }

        $docentes = $query->paginate($per_page);

        // Transformar respuesta
        $data = $docentes->map(function ($docente) {
            return [
                'id' => $docente->id,
                'ci' => $docente->persona->ci,
                'nombre' => $docente->persona->nombre,
                'correo' => $docente->persona->correo,
                'telefono' => '', // Agregar si existe campo
                'estado' => $docente->activo ? 'activo' : 'inactivo',
                'created_at' => $docente->created_at,
                'updated_at' => $docente->updated_at,
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $docentes->currentPage(),
            'last_page' => $docentes->lastPage(),
            'per_page' => $docentes->perPage(),
            'total' => $docentes->total(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/docentes
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ci' => ['required', 'string', 'unique:personas,ci'],
            'nombre' => 'required|string|max:255',
            'correo' => ['required', 'email', 'unique:personas,correo'],
            'telefono' => 'nullable|string|max:20',
            'estado' => 'required|in:activo,inactivo',
        ], [
            'ci.unique' => 'El CI ya está registrado en el sistema',
            'correo.unique' => 'El correo ya está registrado en el sistema',
            'correo.email' => 'El correo debe ser válido',
            'ci.required' => 'El CI es obligatorio',
            'nombre.required' => 'El nombre es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'estado.required' => 'El estado es obligatorio',
        ]);

        try {
            // Crear Persona
            $persona = Persona::create([
                'ci' => $validated['ci'],
                'nombre' => $validated['nombre'],
                'correo' => $validated['correo'],
            ]);

            // Crear Docente
            $docente = Docente::create([
                'id_persona' => $persona->id,
                'activo' => $validated['estado'] === 'activo',
            ]);

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'tabla' => 'docentes',
                    'operacion' => 'crear',
                    'id_registro' => $docente->id,
                    'descripcion' => "Se creó docente: {$persona->nombre} (CI: {$persona->ci})",
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $docente->id,
                    'ci' => $persona->ci,
                    'nombre' => $persona->nombre,
                    'correo' => $persona->correo,
                    'telefono' => $validated['telefono'] ?? '',
                    'estado' => $validated['estado'],
                ],
                'message' => 'Docente creado correctamente',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear docente: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/docentes/:id
     */
    public function show(Docente $docente)
    {
        $docente->load('persona', 'usuario');

        return response()->json([
            'id' => $docente->id,
            'ci' => $docente->persona->ci,
            'nombre' => $docente->persona->nombre,
            'correo' => $docente->persona->correo,
            'telefono' => '',
            'estado' => $docente->activo ? 'activo' : 'inactivo',
            'created_at' => $docente->created_at,
            'updated_at' => $docente->updated_at,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/docentes/:id
     */
    public function update(Request $request, Docente $docente)
    {
        $docente->load('persona');

        $validated = $request->validate([
            'ci' => ['sometimes', 'required', 'string', Rule::unique('personas', 'ci')->ignore($docente->persona->id)],
            'nombre' => 'sometimes|required|string|max:255',
            'correo' => ['sometimes', 'required', 'email', Rule::unique('personas', 'correo')->ignore($docente->persona->id)],
            'telefono' => 'nullable|string|max:20',
            'estado' => 'sometimes|required|in:activo,inactivo',
        ], [
            'ci.unique' => 'El CI ya está registrado en el sistema',
            'correo.unique' => 'El correo ya está registrado en el sistema',
            'correo.email' => 'El correo debe ser válido',
        ]);

        try {
            $cambios = [];

            // Actualizar Persona si es necesario
            if (isset($validated['ci']) && $validated['ci'] !== $docente->persona->ci) {
                $cambios[] = "CI: {$docente->persona->ci} → {$validated['ci']}";
                $docente->persona->ci = $validated['ci'];
            }

            if (isset($validated['nombre']) && $validated['nombre'] !== $docente->persona->nombre) {
                $cambios[] = "Nombre: {$docente->persona->nombre} → {$validated['nombre']}";
                $docente->persona->nombre = $validated['nombre'];
            }

            if (isset($validated['correo']) && $validated['correo'] !== $docente->persona->correo) {
                $cambios[] = "Correo: {$docente->persona->correo} → {$validated['correo']}";
                $docente->persona->correo = $validated['correo'];
            }

            if ($cambios) {
                $docente->persona->save();
            }

            // Actualizar estado del Docente
            if (isset($validated['estado'])) {
                $estado_anterior = $docente->activo ? 'activo' : 'inactivo';
                $estado_nuevo = $validated['estado'];

                if ($estado_anterior !== $estado_nuevo) {
                    $cambios[] = "Estado: {$estado_anterior} → {$estado_nuevo}";
                    $docente->update(['activo' => $estado_nuevo === 'activo']);
                }
            }

            // Registrar en bitácora
            if (auth()->check() && $cambios) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'tabla' => 'docentes',
                    'operacion' => 'editar',
                    'id_registro' => $docente->id,
                    'descripcion' => "Se actualizó docente {$docente->persona->nombre}: " . implode(", ", $cambios),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $docente->id,
                    'ci' => $docente->persona->ci,
                    'nombre' => $docente->persona->nombre,
                    'correo' => $docente->persona->correo,
                    'telefono' => $validated['telefono'] ?? '',
                    'estado' => $docente->activo ? 'activo' : 'inactivo',
                ],
                'message' => 'Docente actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar docente: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the estado of the resource.
     * PATCH /api/docentes/:id/estado
     */
    public function updateEstado(Request $request, Docente $docente)
    {
        $docente->load('persona');

        $validated = $request->validate([
            'estado' => 'required|in:activo,inactivo',
        ]);

        try {
            $estado_anterior = $docente->activo ? 'activo' : 'inactivo';
            $estado_nuevo = $validated['estado'];

            if ($estado_anterior === $estado_nuevo) {
                return response()->json([
                    'success' => false,
                    'message' => "El docente ya está {$estado_nuevo}",
                ], 422);
            }

            $docente->update([
                'activo' => $estado_nuevo === 'activo',
            ]);

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'docentes',
                    'operacion' => 'cambiar_estado',
                    'id_registro' => $docente->id,
                    'descripcion' => "Estado de docente {$docente->persona->nombre} cambió de {$estado_anterior} a {$estado_nuevo}",
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $docente->id,
                    'estado' => $estado_nuevo,
                ],
                'message' => 'Estado actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/docentes/:id
     */
    public function destroy(Docente $docente)
    {
        try {
            $docente->load('persona');
            $nombre_docente = $docente->persona->nombre;
            $ci_docente = $docente->persona->ci;

            // Registrar en bitácora antes de eliminar
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'tabla' => 'docentes',
                    'operacion' => 'eliminar',
                    'id_registro' => $docente->id,
                    'descripcion' => "Se eliminó docente: {$nombre_docente} (CI: {$ci_docente})",
                ]);
            }

            // Eliminar Docente
            $docente->delete();

            return response()->json([
                'success' => true,
                'message' => 'Docente eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar docente: ' . $e->getMessage(),
            ], 500);
        }
    }
}
