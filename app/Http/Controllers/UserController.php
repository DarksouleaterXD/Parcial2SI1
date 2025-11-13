<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Persona;
use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios con sus roles
     * GET /api/usuarios
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['persona', 'roles']);

            // Búsqueda
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhereHas('persona', function ($pq) use ($search) {
                          $pq->where('ci', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Filtrar por rol antiguo
            if ($request->has('rol') && $request->rol) {
                $query->where('rol', $request->rol);
            }

            // Filtrar activos
            if ($request->has('activo')) {
                $query->where('activo', $request->activo === 'true' || $request->activo === '1');
            }

            $perPage = $request->input('per_page', 15);
            $usuarios = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $usuarios->items(),
                'pagination' => [
                    'total' => $usuarios->total(),
                    'per_page' => $usuarios->perPage(),
                    'current_page' => $usuarios->currentPage(),
                    'last_page' => $usuarios->lastPage(),
                    'from' => $usuarios->firstItem(),
                    'to' => $usuarios->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un usuario específico
     * GET /api/usuarios/{id}
     */
    public function show($id)
    {
        try {
            $usuario = User::with(['persona', 'roles.permisos'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $usuario,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo usuario
     * POST /api/usuarios
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'email' => 'required|email|unique:usuarios,email',
                'password' => 'required|string|min:8',
                'rol' => 'required|in:admin,coordinador,autoridad,docente',
                'ci' => 'nullable|string|unique:personas,ci',
                'apellido' => 'nullable|string|max:255',
                'apellido_paterno' => 'nullable|string|max:255',
                'apellido_materno' => 'nullable|string|max:255',
                'telefono' => 'nullable|string|max:20',
                'roles_rbac' => 'nullable|array',
                'roles_rbac.*' => 'exists:roles,id',
            ], [
                'nombre.required' => 'El nombre es obligatorio',
                'email.required' => 'El email es obligatorio',
                'email.unique' => 'Ya existe un usuario con este email',
                'password.required' => 'La contraseña es obligatoria',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres',
                'ci.unique' => 'Ya existe una persona con este CI',
            ]);

            // Crear persona si se proporciona CI
            $personaId = null;
            if (isset($validated['ci'])) {
                // Si hay apellidos separados, usarlos, si no, usar apellido completo
                $apellidoPaterno = $validated['apellido_paterno'] ?? ($validated['apellido'] ?? '');
                $apellidoMaterno = $validated['apellido_materno'] ?? '';

                $persona = Persona::create([
                    'nombre' => $validated['nombre'],
                    'apellido_paterno' => $apellidoPaterno,
                    'apellido_materno' => $apellidoMaterno,
                    'apellido' => $validated['apellido'] ?? ($apellidoPaterno . ' ' . $apellidoMaterno),
                    'ci' => $validated['ci'],
                    'correo' => $validated['email'],
                    'telefono' => $validated['telefono'] ?? null,
                ]);
                $personaId = $persona->id;
            }

            // Crear usuario
            $usuario = User::create([
                'nombre' => $validated['nombre'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'rol' => $validated['rol'],
                'activo' => true,
                'id_persona' => $personaId,
            ]);

            // Actualizar persona con id_usuario si existe
            if ($personaId) {
                Persona::where('id', $personaId)->update(['id_usuario' => $usuario->id]);
            }

            // Si el rol es docente, crear registro en tabla docentes
            if ($validated['rol'] === 'docente') {
                // Si no se creó persona, crearla ahora (es requerida para docentes)
                if (!$personaId) {
                    $apellidoPaterno = $validated['apellido_paterno'] ?? ($validated['apellido'] ?? '');
                    $apellidoMaterno = $validated['apellido_materno'] ?? '';

                    $persona = Persona::create([
                        'nombre' => $validated['nombre'],
                        'apellido_paterno' => $apellidoPaterno,
                        'apellido_materno' => $apellidoMaterno,
                        'apellido' => $validated['apellido'] ?? ($apellidoPaterno . ' ' . $apellidoMaterno),
                        'ci' => $validated['ci'] ?? 'TEMP-' . time(),
                        'correo' => $validated['email'],
                        'telefono' => $validated['telefono'] ?? null,
                        'id_usuario' => $usuario->id,
                    ]);
                    $personaId = $persona->id;

                    // Actualizar el usuario con el id_persona
                    $usuario->update(['id_persona' => $personaId]);
                }

                // Crear docente
                \App\Models\Docente::create([
                    'id_persona' => $personaId,
                    'activo' => true,
                ]);
            }

            // Asignar roles RBAC
            if (isset($validated['roles_rbac']) && is_array($validated['roles_rbac'])) {
                $usuario->roles()->attach($validated['roles_rbac'], [
                    'asignado_en' => now(),
                    'asignado_por' => auth()->id(),
                ]);
            }

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'usuarios',
                    'operacion' => 'crear',
                    'id_registro' => $usuario->id,
                    'descripcion' => "Usuario creado: {$usuario->email} con rol {$usuario->rol}",
                ]);
            }

            $usuario->load(['persona', 'roles']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado correctamente' . ($validated['rol'] === 'docente' ? ' y registrado como docente' : ''),
                'data' => $usuario,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un usuario
     * PUT /api/usuarios/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $usuario = User::findOrFail($id);

            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:255',
                'email' => ['sometimes', 'required', 'email', Rule::unique('usuarios', 'email')->ignore($id)],
                'password' => 'sometimes|nullable|string|min:8',
                'rol' => 'sometimes|required|in:admin,coordinador,autoridad,docente',
                'activo' => 'sometimes|boolean',
                'roles_rbac' => 'nullable|array',
                'roles_rbac.*' => 'exists:roles,id',
            ]);

            $cambios = [];

            if (isset($validated['nombre']) && $validated['nombre'] !== $usuario->nombre) {
                $cambios[] = "Nombre: {$usuario->nombre} → {$validated['nombre']}";
                $usuario->nombre = $validated['nombre'];
            }

            if (isset($validated['email']) && $validated['email'] !== $usuario->email) {
                $cambios[] = "Email: {$usuario->email} → {$validated['email']}";
                $usuario->email = $validated['email'];
            }

            if (isset($validated['password']) && $validated['password']) {
                $cambios[] = "Contraseña actualizada";
                $usuario->password = Hash::make($validated['password']);
            }

            if (isset($validated['rol']) && $validated['rol'] !== $usuario->rol) {
                $cambios[] = "Rol: {$usuario->rol} → {$validated['rol']}";
                $rolAnterior = $usuario->rol;
                $usuario->rol = $validated['rol'];

                // Si cambia a docente, crear registro en tabla docentes
                if ($validated['rol'] === 'docente' && $rolAnterior !== 'docente') {
                    // Verificar si ya existe un registro de docente
                    $docenteExiste = \App\Models\Docente::whereHas('persona', function($q) use ($usuario) {
                        $q->where('id_usuario', $usuario->id);
                    })->exists();

                    if (!$docenteExiste) {
                        // Si no tiene persona, crearla
                        if (!$usuario->id_persona) {
                            $persona = Persona::create([
                                'nombre' => $usuario->nombre,
                                'apellido_paterno' => '',
                                'apellido_materno' => '',
                                'ci' => 'TEMP-' . time() . '-' . $usuario->id,
                                'correo' => $usuario->email,
                                'id_usuario' => $usuario->id,
                            ]);
                            $usuario->id_persona = $persona->id;
                        }

                        // Crear docente
                        \App\Models\Docente::create([
                            'id_persona' => $usuario->id_persona,
                            'activo' => $usuario->activo,
                        ]);

                        $cambios[] = "Registrado como docente";
                    }
                }
            }

            if (isset($validated['activo']) && $validated['activo'] !== $usuario->activo) {
                $cambios[] = "Estado: " . ($usuario->activo ? 'activo' : 'inactivo') . " → " . ($validated['activo'] ? 'activo' : 'inactivo');
                $usuario->activo = $validated['activo'];
            }

            $usuario->save();

            // Actualizar roles RBAC
            if (isset($validated['roles_rbac'])) {
                $rolesAntes = $usuario->roles()->count();
                $usuario->roles()->sync($validated['roles_rbac']);
                $rolesDespues = count($validated['roles_rbac']);

                if ($rolesAntes !== $rolesDespues) {
                    $cambios[] = "Roles RBAC: {$rolesAntes} → {$rolesDespues}";
                }
            }

            // Registrar en bitácora
            if (auth()->check() && $cambios) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'usuarios',
                    'operacion' => 'editar',
                    'id_registro' => $usuario->id,
                    'descripcion' => "Usuario actualizado: {$usuario->email}. Cambios: " . implode(", ", $cambios),
                ]);
            }

            $usuario->load(['persona', 'roles']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'data' => $usuario,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un usuario
     * DELETE /api/usuarios/{id}
     */
    public function destroy($id)
    {
        try {
            $usuario = User::findOrFail($id);

            // No permitir eliminar al propio usuario
            if (auth()->id() === $usuario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propia cuenta',
                ], 403);
            }

            // No permitir eliminar el último admin
            if ($usuario->rol === 'admin') {
                $adminCount = User::where('rol', 'admin')->where('activo', true)->count();
                if ($adminCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar el último administrador del sistema',
                    ], 403);
                }
            }

            $nombreUsuario = $usuario->nombre;
            $emailUsuario = $usuario->email;

            // Desasignar roles RBAC
            $usuario->roles()->detach();

            $usuario->delete();

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'usuarios',
                    'operacion' => 'eliminar',
                    'id_registro' => $id,
                    'descripcion' => "Usuario eliminado: {$nombreUsuario} ({$emailUsuario})",
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado correctamente',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar usuario: ' . $e->getMessage(),
            ], 500);
        }
    }
}
