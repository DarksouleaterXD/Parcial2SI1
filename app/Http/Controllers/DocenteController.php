<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Persona;
use App\Models\User;
use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

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
                'telefono' => $docente->persona->telefono ?? '',
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
            'correo' => ['required', 'email', 'unique:personas,correo', 'unique:usuarios,email'],
            'telefono' => 'nullable|digits_between:7,15',
            'contrasena' => 'required|string|min:8',
            'estado' => 'required|in:activo,inactivo',
        ], [
            'ci.unique' => 'El CI ya está registrado en el sistema',
            'correo.unique' => 'El correo ya está registrado en el sistema',
            'correo.email' => 'El correo debe ser válido',
            'ci.required' => 'El CI es obligatorio',
            'nombre.required' => 'El nombre es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'contrasena.required' => 'La contraseña es obligatoria',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres',
            'estado.required' => 'El estado es obligatorio',
            'telefono.digits_between' => 'El teléfono debe tener entre 7 y 15 dígitos',
        ]);

        try {
            // Crear Usuario
            $usuario = User::create([
                'nombre' => $validated['nombre'],
                'email' => $validated['correo'],
                'password' => Hash::make($validated['contrasena']),
                'rol' => 'docente',
                'activo' => $validated['estado'] === 'activo',
            ]);

            // Crear Persona
            $persona = Persona::create([
                'ci' => $validated['ci'],
                'nombre' => $validated['nombre'],
                'correo' => $validated['correo'],
                'telefono' => $validated['telefono'] ?? null,
                'id_usuario' => $usuario->id,
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
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'docentes',
                    'operacion' => 'crear',
                    'id_registro' => $docente->id,
                    'descripcion' => "Se creó docente: {$persona->nombre} (CI: {$persona->ci}) con cuenta de usuario {$usuario->email}",
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $docente->id,
                    'ci' => $persona->ci,
                    'nombre' => $persona->nombre,
                    'correo' => $persona->correo,
                    'telefono' => $persona->telefono ?? '',
                    'estado' => $validated['estado'],
                ],
                'message' => 'Docente creado correctamente. Puede usar su correo y contraseña para entrar al sistema.',
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
            'telefono' => $docente->persona->telefono ?? '',
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
        $docente->load('persona', 'usuario');

        $validated = $request->validate([
            'ci' => ['sometimes', 'required', 'string', Rule::unique('personas', 'ci')->ignore($docente->persona->id)],
            'nombre' => 'sometimes|required|string|max:255',
            'correo' => ['sometimes', 'required', 'email', Rule::unique('personas', 'correo')->ignore($docente->persona->id), Rule::unique('usuarios', 'email')->ignore($docente->usuario->id ?? null)],
            'telefono' => 'nullable|digits_between:7,15',
            'contrasena' => 'nullable|string|min:8',
            'estado' => 'sometimes|required|in:activo,inactivo',
        ], [
            'ci.unique' => 'El CI ya está registrado en el sistema',
            'correo.unique' => 'El correo ya está registrado en el sistema',
            'correo.email' => 'El correo debe ser válido',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres',
            'telefono.digits_between' => 'El teléfono debe tener entre 7 y 15 dígitos',
        ]);

        try {
            $cambios = [];

            // Actualizar Usuario si existe y hay cambios en correo o contraseña
            if ($docente->usuario) {
                if (isset($validated['nombre']) && $validated['nombre'] !== $docente->usuario->nombre) {
                    $cambios[] = "Nombre usuario: {$docente->usuario->nombre} → {$validated['nombre']}";
                    $docente->usuario->nombre = $validated['nombre'];
                }

                if (isset($validated['correo']) && $validated['correo'] !== $docente->usuario->email) {
                    $cambios[] = "Email usuario: {$docente->usuario->email} → {$validated['correo']}";
                    $docente->usuario->email = $validated['correo'];
                }

                if (isset($validated['contrasena']) && !empty($validated['contrasena'])) {
                    $cambios[] = "Contraseña del usuario actualizada";
                    $docente->usuario->password = Hash::make($validated['contrasena']);
                }

                if ($cambios) {
                    $docente->usuario->save();
                }
            }

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
                $cambios[] = "Correo persona: {$docente->persona->correo} → {$validated['correo']}";
                $docente->persona->correo = $validated['correo'];
            }

            if (isset($validated['telefono']) && $validated['telefono'] !== $docente->persona->telefono) {
                $cambios[] = "Teléfono: {$docente->persona->telefono} → {$validated['telefono']}";
                $docente->persona->telefono = $validated['telefono'];
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

                    // Actualizar también el usuario si existe
                    if ($docente->usuario) {
                        $docente->usuario->update(['activo' => $estado_nuevo === 'activo']);
                    }
                }
            }

            // Registrar en bitácora
            if (auth()->check() && $cambios) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'ip_address' => IpHelper::getClientIp(),
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
                    'telefono' => $docente->persona->telefono ?? '',
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
                    'ip_address' => IpHelper::getClientIp(),
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
