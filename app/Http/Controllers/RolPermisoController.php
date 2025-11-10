<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Permiso;
use App\Models\Modulo;
use App\Models\Accion;
use App\Models\User;
use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolPermisoController extends Controller
{
    /**
     * Listar todos los roles
     * GET /api/roles
     */
    public function index(Request $request)
    {
        try {
            $query = Rol::with(['permisos.modulo', 'permisos.accion']);

            // Búsqueda
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('descripcion', 'LIKE', "%{$search}%");
            }

            // Filtrar activos
            if ($request->has('activo')) {
                $query->where('activo', $request->activo === 'true' || $request->activo === '1');
            }

            $perPage = $request->input('per_page', 15);
            $roles = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $roles->items(),
                'pagination' => [
                    'total' => $roles->total(),
                    'per_page' => $roles->perPage(),
                    'current_page' => $roles->currentPage(),
                    'last_page' => $roles->lastPage(),
                    'from' => $roles->firstItem(),
                    'to' => $roles->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener roles: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo rol
     * POST /api/roles
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:100|unique:roles,nombre',
                'descripcion' => 'nullable|string|max:255',
                'permisos' => 'nullable|array',
                'permisos.*' => 'exists:permisos,id',
            ], [
                'nombre.required' => 'El nombre del rol es obligatorio',
                'nombre.unique' => 'Ya existe un rol con ese nombre',
                'permisos.*.exists' => 'Uno o más permisos no son válidos',
            ]);

            $rol = Rol::create([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'es_sistema' => false,
                'activo' => true,
            ]);

            // Asignar permisos
            if (isset($validated['permisos']) && is_array($validated['permisos'])) {
                $rol->permisos()->sync($validated['permisos']);
            }

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'roles',
                    'operacion' => 'crear',
                    'id_registro' => $rol->id,
                    'descripcion' => "Rol creado: {$rol->nombre} con " . count($validated['permisos'] ?? []) . " permisos",
                ]);
            }

            $rol->load(['permisos.modulo', 'permisos.accion']);

            return response()->json([
                'success' => true,
                'message' => 'Rol creado correctamente',
                'data' => $rol,
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
                'message' => 'Error al crear rol: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un rol específico
     * GET /api/roles/{id}
     */
    public function show($id)
    {
        try {
            $rol = Rol::with(['permisos.modulo', 'permisos.accion', 'usuarios'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $rol,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener rol: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un rol
     * PUT /api/roles/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $rol = Rol::findOrFail($id);

            $validated = $request->validate([
                'nombre' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('roles', 'nombre')->ignore($id)],
                'descripcion' => 'nullable|string|max:255',
                'permisos' => 'nullable|array',
                'permisos.*' => 'exists:permisos,id',
                'activo' => 'sometimes|boolean',
            ]);

            $cambios = [];

            if (isset($validated['nombre']) && $validated['nombre'] !== $rol->nombre) {
                $cambios[] = "Nombre: {$rol->nombre} → {$validated['nombre']}";
                $rol->nombre = $validated['nombre'];
            }

            if (isset($validated['descripcion']) && $validated['descripcion'] !== $rol->descripcion) {
                $cambios[] = "Descripción actualizada";
                $rol->descripcion = $validated['descripcion'];
            }

            if (isset($validated['activo']) && $validated['activo'] !== $rol->activo) {
                $cambios[] = "Estado: " . ($rol->activo ? 'activo' : 'inactivo') . " → " . ($validated['activo'] ? 'activo' : 'inactivo');
                $rol->activo = $validated['activo'];
            }

            $rol->save();

            // Actualizar permisos
            if (isset($validated['permisos'])) {
                $permisosAntes = $rol->permisos()->count();
                $rol->permisos()->sync($validated['permisos']);
                $permisosDespues = count($validated['permisos']);

                if ($permisosAntes !== $permisosDespues) {
                    $cambios[] = "Permisos: {$permisosAntes} → {$permisosDespues}";
                }
            }

            // Registrar en bitácora
            if (auth()->check() && $cambios) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'roles',
                    'operacion' => 'editar',
                    'id_registro' => $rol->id,
                    'descripcion' => "Rol actualizado: {$rol->nombre}. Cambios: " . implode(", ", $cambios),
                ]);
            }

            $rol->load(['permisos.modulo', 'permisos.accion']);

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado correctamente',
                'data' => $rol,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
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
                'message' => 'Error al actualizar rol: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un rol
     * DELETE /api/roles/{id}
     */
    public function destroy($id)
    {
        try {
            $rol = Rol::findOrFail($id);

            // No permitir eliminar Super Administrador y Administrador
            if (in_array($rol->nombre, ['Super Administrador', 'Administrador'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el rol "' . $rol->nombre . '" porque es crítico para el sistema',
                ], 403);
            }

            // Verificar si el rol está en uso
            $usuariosCount = $rol->usuarios()->count();
            if ($usuariosCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar el rol porque está asignado a {$usuariosCount} usuario(s)",
                ], 409);
            }

            $nombreRol = $rol->nombre;
            $rol->delete();

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'roles',
                    'operacion' => 'eliminar',
                    'id_registro' => $id,
                    'descripcion' => "Rol eliminado: {$nombreRol}",
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado correctamente',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar rol: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar todos los permisos disponibles
     * GET /api/permisos
     */
    public function listarPermisos(Request $request)
    {
        try {
            $permisos = Permiso::with(['modulo', 'accion'])
                ->orderBy('id_modulo')
                ->orderBy('id_accion')
                ->get();

            // Agrupar por módulo para facilitar la UI
            $permisosPorModulo = [];

            foreach ($permisos as $permiso) {
                $moduloNombre = $permiso->modulo->nombre;

                if (!isset($permisosPorModulo[$moduloNombre])) {
                    $permisosPorModulo[$moduloNombre] = [
                        'modulo' => $permiso->modulo->nombre,
                        'permisos' => [],
                    ];
                }

                $permisosPorModulo[$moduloNombre]['permisos'][] = [
                    'id' => $permiso->id,
                    'accion' => $permiso->accion->nombre,
                    'descripcion' => $permiso->descripcion ?? ucfirst($permiso->accion->nombre) . ' ' . $permiso->modulo->nombre,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => array_values($permisosPorModulo),
                'total' => $permisos->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Asignar rol a usuario
     * POST /api/usuarios/{userId}/roles
     */
    public function asignarRolAUsuario(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            $validated = $request->validate([
                'rol_id' => 'required|exists:roles,id',
            ]);

            $rol = Rol::findOrFail($validated['rol_id']);

            // Verificar si ya tiene el rol
            if ($user->roles()->where('id_rol', $rol->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario ya tiene este rol asignado',
                ], 409);
            }

            $user->roles()->attach($rol->id, [
                'asignado_en' => now(),
                'asignado_por' => auth()->id(),
            ]);

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'usuario_rol',
                    'operacion' => 'crear',
                    'id_registro' => $user->id,
                    'descripcion' => "Rol '{$rol->nombre}' asignado al usuario {$user->nombre}",
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rol asignado correctamente',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o rol no encontrado',
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
                'message' => 'Error al asignar rol: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remover rol de usuario
     * DELETE /api/usuarios/{userId}/roles/{rolId}
     */
    public function removerRolDeUsuario($userId, $rolId)
    {
        try {
            $user = User::findOrFail($userId);
            $rol = Rol::findOrFail($rolId);

            if (!$user->roles()->where('id_rol', $rolId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no tiene este rol asignado',
                ], 404);
            }

            $user->roles()->detach($rolId);

            // Registrar en bitácora
            if (auth()->check()) {
                Bitacora::create([
                    'id_usuario' => auth()->id(),
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'usuario_rol',
                    'operacion' => 'eliminar',
                    'id_registro' => $user->id,
                    'descripcion' => "Rol '{$rol->nombre}' removido del usuario {$user->nombre}",
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rol removido correctamente',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o rol no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al remover rol: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener módulos del sistema
     * GET /api/modulos
     */
    public function listarModulos()
    {
        try {
            $modulos = Modulo::where('activo', true)
                ->orderBy('orden')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $modulos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener módulos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener acciones disponibles
     * GET /api/acciones
     */
    public function listarAcciones()
    {
        try {
            $acciones = Accion::all();

            return response()->json([
                'success' => true,
                'data' => $acciones,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener acciones: ' . $e->getMessage(),
            ], 500);
        }
    }
}
