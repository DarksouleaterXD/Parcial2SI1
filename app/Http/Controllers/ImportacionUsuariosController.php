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

            // Importar y CREAR usuarios (soloValidar = false)
            $import = new UsuariosImport(
                false, // soloValidar = false (crear usuarios)
                true,  // validarDuplicadosBD = true
                $generarPasswords,
                $rolRbacDefecto
            );

            Excel::import($import, $archivo);

            $resultados = $import->getResultados();
            $estadisticas = $import->getEstadisticas();

            // Contar usuarios creados exitosamente
            $usuariosCreados = array_filter($resultados, fn($r) => isset($r['creado']) && $r['creado']);
            $erroresCreacion = array_filter($resultados, fn($r) => !$r['valido']);

            \Log::info('Importación completada:', [
                'total' => count($resultados),
                'creados' => count($usuariosCreados),
                'errores' => count($erroresCreacion),
            ]);

            // Registrar en bitácora
            $user = auth()->user();
            if ($user) {
                Bitacora::create([
                    'id_usuario' => $user->id,
                    'ip_address' => IpHelper::getClientIp(),
                    'tabla' => 'usuarios',
                    'operacion' => 'importacion_masiva',
                    'id_registro' => null,
                    'descripcion' => "Importación masiva completada: {$estadisticas['total']} filas procesadas, " . count($usuariosCreados) . " usuarios creados",
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => count($usuariosCreados) . ' usuario(s) creado(s) exitosamente',
                'data' => [
                    'estadisticas' => [
                        'total_procesado' => count($resultados),
                        'usuarios_creados' => count($usuariosCreados),
                        'errores' => count($erroresCreacion),
                    ],
                    'resultados' => $resultados,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en importación:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
