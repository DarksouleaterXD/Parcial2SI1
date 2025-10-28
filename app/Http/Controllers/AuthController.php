<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * CASO 16: Login de usuario
     * POST /api/login
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            // Buscar usuario
            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email o contraseña incorrectos'
                ], 401);
            }

            // Verificar si está activo
            if (!$user->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario inactivo'
                ], 403);
            }

            // Crear token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'user' => $user->load('persona'),
                    'token' => $token,
                    'rol' => $user->rol,
                    'is_admin' => $user->rol === 'admin'
                ]
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
                'message' => 'Error en login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CASO 17: Logout de usuario
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CASO 18: Obtener perfil del usuario autenticado
     * GET /api/perfil
     */
    public function perfil(Request $request)
    {
        try {
            $user = $request->user()->load('persona');

            return response()->json([
                'success' => true,
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CASO 19: Cambiar contraseña
     * POST /api/cambiar-contrasena
     */
    public function cambiarContrasena(Request $request)
    {
        try {
            $validated = $request->validate([
                'password_actual' => 'required|string',
                'password_nueva' => 'required|string|min:8|confirmed'
            ]);

            $user = $request->user();

            if (!Hash::check($validated['password_actual'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 401);
            }

            $user->update([
                'password' => Hash::make($validated['password_nueva'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar contraseña',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CASO 20: Listar todos los usuarios (SOLO ADMIN)
     * GET /api/usuarios-lista
     */
    public function listarUsuarios(Request $request)
    {
        try {
            if ($request->user()->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para esta acción'
                ], 403);
            }

            $usuarios = User::with('persona')->get();

            return response()->json([
                'success' => true,
                'data' => $usuarios,
                'count' => $usuarios->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CASO 21: Registrar un nuevo usuario (SOLO ADMIN)
     * POST /api/registrar-usuario
     */
    public function registrarUsuario(Request $request)
    {
        try {
            // Verificar que sea admin
            if ($request->user()->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo administradores pueden crear usuarios'
                ], 403);
            }

            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'email' => 'required|email|unique:usuarios,email',
                'password' => 'required|string|min:8',
                'ci' => 'required|string|unique:personas,ci',
                'rol' => 'required|in:admin,docente,estudiante'
            ]);

            // Crear persona
            $persona = Persona::create([
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'correo' => $validated['email'],
                'ci' => $validated['ci'],
            ]);

            // Crear usuario
            $user = User::create([
                'nombre' => $validated['nombre'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'rol' => $validated['rol'],
                'activo' => true,
                'id_persona' => $persona->id_persona,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => $user->load('persona')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
