<?php

namespace App\Http\Controllers;

use App\Exports\PlantillaUsuariosExport;
use App\Imports\UsuariosImport;
use App\Models\User;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportacionUsuariosController extends Controller
{
    /**
     * Descargar plantilla CSV/Excel
     * GET /api/usuarios/importar/plantilla
     */
    public function descargarPlantilla(Request $request)
    {
        try {
            $formato = $request->query('formato', 'xlsx'); // xlsx o csv

            $filename = 'plantilla_usuarios.' . $formato;

            if ($formato === 'csv') {
                return Excel::download(new PlantillaUsuariosExport, $filename, \Maatwebsite\Excel\Excel::CSV);
            }

            return Excel::download(new PlantillaUsuariosExport, $filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar plantilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar archivo subido sin crear usuarios
     * POST /api/usuarios/importar/validar
     */
    public function validarArchivo(Request $request)
    {
        try {
            // Validar que se envió un archivo
            $request->validate([
                'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120', // 5MB max
            ]);

            $archivo = $request->file('archivo');

            // Importar y validar
            $import = new UsuariosImport(true);
            Excel::import($import, $archivo);

            $resultados = $import->getResultados();
            $estadisticas = $import->getEstadisticas();

            // No registramos en bitácora la validación porque no modifica datos
            // Solo se registrará cuando se confirme la importación

            return response()->json([
                'success' => true,
                'message' => 'Archivo validado exitosamente',
                'data' => [
                    'estadisticas' => $estadisticas,
                    'resultados' => $resultados,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo inválido',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar importación y crear usuarios
     * POST /api/usuarios/importar/confirmar
     */
    public function confirmarImportacion(Request $request)
    {
        try {
            // Validar datos
            $request->validate([
                'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120',
                'rol_rbac_defecto' => 'nullable|integer|exists:roles,id',
                'generar_passwords' => 'boolean',
                'enviar_emails' => 'boolean',
            ]);

            $archivo = $request->file('archivo');
            $rolRbacDefecto = $request->input('rol_rbac_defecto');
            $generarPasswords = $request->input('generar_passwords', true);
            $enviarEmails = $request->input('enviar_emails', false);

            // Importar y validar (SIN validar duplicados en BD porque ya se validó antes)
            $import = new UsuariosImport(true, false); // false = no validar duplicados en BD
            Excel::import($import, $archivo);

            $resultados = $import->getResultados();
            $estadisticas = $import->getEstadisticas();

            \Log::info('Resultados de importación:', [
                'total' => count($resultados),
                'estadisticas' => $estadisticas,
                'resultados' => $resultados,
            ]);

            // Filtrar solo filas válidas
            $filasValidas = array_filter($resultados, fn($r) => $r['valido']);

            \Log::info('Filas válidas filtradas:', [
                'cantidad' => count($filasValidas),
                'filas' => $filasValidas,
            ]);

            if (empty($filasValidas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay filas válidas para importar',
                ], 422);
            }

            $usuariosCreados = [];
            $erroresCreacion = [];

            DB::beginTransaction();

            try {
                foreach ($filasValidas as $fila) {
                    try {
                        $datos = $fila['datos'];

                        // Usar password del Excel o generar uno
                        $password = null;
                        if (!empty($datos['password'])) {
                            // Usar el password proporcionado en el Excel
                            $password = $datos['password'];
                        } elseif ($generarPasswords) {
                            // Generar password aleatorio
                            $password = Str::random(12);
                        } else {
                            // Password temporal por defecto
                            $password = 'temporal123';
                        }

                        // Crear Persona si tiene CI
                        $persona = null;
                        if (!empty($datos['ci'])) {
                            $persona = Persona::create([
                                'ci' => $datos['ci'],
                                'correo' => $datos['email'], // Campo correo es requerido en la tabla personas
                                'nombre' => $datos['nombre'],
                                'apellido_paterno' => $datos['apellido_paterno'],
                                'apellido_materno' => $datos['apellido_materno'],
                                'telefono' => $datos['telefono'],
                                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                            ]);
                        }

                        // Crear Usuario
                        $usuario = User::create([
                            'nombre' => $datos['nombre'] . ' ' . $datos['apellido_paterno'],
                            'email' => $datos['email'],
                            'password' => Hash::make($password),
                            'rol' => $datos['rol'],
                            'id_persona' => $persona ? $persona->id : null,
                        ]);

                        // Asignar rol RBAC si se especificó
                        if ($rolRbacDefecto) {
                            DB::table('usuario_rol')->insert([
                                'id_usuario' => $usuario->id,
                                'id_rol' => $rolRbacDefecto,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            // Asignar rol RBAC según el rol del sistema
                            $rolRbac = null;
                            switch ($datos['rol']) {
                                case 'admin':
                                    $rolRbac = Rol::where('nombre', 'Administrador')->first();
                                    break;
                                case 'coordinador':
                                    $rolRbac = Rol::where('nombre', 'Coordinador')->first();
                                    break;
                                case 'docente':
                                    $rolRbac = Rol::where('nombre', 'Docente')->first();
                                    break;
                                case 'autoridad':
                                    $rolRbac = Rol::where('nombre', 'Autoridad')->first();
                                    break;
                            }

                            if ($rolRbac) {
                                DB::table('usuario_rol')->insert([
                                    'id_usuario' => $usuario->id,
                                    'id_rol' => $rolRbac->id,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }

                        $usuariosCreados[] = [
                            'fila' => $fila['fila'],
                            'usuario_id' => $usuario->id,
                            'email' => $usuario->email,
                            'password' => $password,
                        ];

                        // TODO: Enviar email con credenciales si $enviarEmails es true

                    } catch (\Exception $e) {
                        \Log::error('Error al crear usuario en fila ' . $fila['fila'], [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'datos' => $fila['datos'],
                        ]);

                        $erroresCreacion[] = [
                            'fila' => $fila['fila'],
                            'error' => $e->getMessage(),
                        ];
                    }
                }

                \Log::info('Usuarios creados exitosamente:', [
                    'cantidad' => count($usuariosCreados),
                    'usuarios' => $usuariosCreados,
                ]);

                \Log::info('Errores durante creación:', [
                    'cantidad' => count($erroresCreacion),
                    'errores' => $erroresCreacion,
                ]);

                DB::commit();

                // Registrar en bitácora
                $user = auth()->user();
                Bitacora::create([
                    'id_usuario' => $user->id,
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'usuarios',
                    'operacion' => 'importacion_masiva',
                    'id_registro' => null,
                    'descripcion' => "Importación masiva completada: {$estadisticas['total']} filas procesadas, " . count($usuariosCreados) . " usuarios creados",
                ]);

                return response()->json([
                    'success' => true,
                    'message' => count($usuariosCreados) . ' usuario(s) creado(s) exitosamente',
                    'data' => [
                        'estadisticas' => [
                            'total_procesado' => $estadisticas['total'],
                            'usuarios_creados' => count($usuariosCreados),
                            'errores_validacion' => $estadisticas['errores'],
                            'errores_creacion' => count($erroresCreacion),
                        ],
                        'usuarios_creados' => $usuariosCreados,
                        'errores_creacion' => $erroresCreacion,
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de importaciones
     * GET /api/usuarios/importar/historial
     */
    public function historial(Request $request)
    {
        try {
            $historial = Bitacora::where('tabla', 'usuarios')
                ->whereIn('operacion', ['validar_importacion', 'importacion_masiva'])
                ->with('usuario:id,nombre,email')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $historial->items(),
                'pagination' => [
                    'total' => $historial->total(),
                    'count' => $historial->count(),
                    'per_page' => $historial->perPage(),
                    'current_page' => $historial->currentPage(),
                    'total_pages' => $historial->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }
}
