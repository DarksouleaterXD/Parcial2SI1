<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Modulo;
use App\Models\Accion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RBACSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // 1. CREAR ACCIONES GENÉRICAS
            $acciones = [
                ['nombre' => 'crear', 'descripcion' => 'Crear nuevo registro'],
                ['nombre' => 'ver', 'descripcion' => 'Ver/listar registros'],
                ['nombre' => 'editar', 'descripcion' => 'Editar registro existente'],
                ['nombre' => 'eliminar', 'descripcion' => 'Eliminar registro'],
                ['nombre' => 'exportar', 'descripcion' => 'Exportar datos'],
                ['nombre' => 'importar', 'descripcion' => 'Importar datos'],
                ['nombre' => 'gestionar_estado', 'descripcion' => 'Cambiar estado activo/inactivo'],
            ];

            foreach ($acciones as $accionData) {
                Accion::firstOrCreate(
                    ['nombre' => $accionData['nombre']],
                    ['descripcion' => $accionData['descripcion']]
                );
            }

            echo "✓ Acciones creadas\n";

            // 2. CREAR MÓDULOS DEL SISTEMA
            $modulos = [
                ['nombre' => 'dashboard', 'descripcion' => 'Panel de control', 'icono' => 'dashboard', 'orden' => 1],
                ['nombre' => 'periodos', 'descripcion' => 'Gestión de periodos académicos', 'icono' => 'calendar', 'orden' => 2],
                ['nombre' => 'materias', 'descripcion' => 'Gestión de materias', 'icono' => 'book', 'orden' => 3],
                ['nombre' => 'aulas', 'descripcion' => 'Gestión de aulas', 'icono' => 'building', 'orden' => 4],
                ['nombre' => 'grupos', 'descripcion' => 'Gestión de grupos', 'icono' => 'users', 'orden' => 5],
                ['nombre' => 'docentes', 'descripcion' => 'Gestión de docentes', 'icono' => 'user-tie', 'orden' => 6],
                ['nombre' => 'horarios', 'descripcion' => 'Gestión de horarios', 'icono' => 'clock', 'orden' => 7],
                ['nombre' => 'carreras', 'descripcion' => 'Gestión de carreras', 'icono' => 'graduation-cap', 'orden' => 8],
                ['nombre' => 'bitacora', 'descripcion' => 'Registro de auditoría', 'icono' => 'file-text', 'orden' => 9],
                ['nombre' => 'roles', 'descripcion' => 'Gestión de roles y permisos', 'icono' => 'shield', 'orden' => 10],
                ['nombre' => 'usuarios', 'descripcion' => 'Gestión de usuarios', 'icono' => 'user', 'orden' => 11],
            ];

            $modulosCreados = [];
            foreach ($modulos as $moduloData) {
                $modulo = Modulo::firstOrCreate(
                    ['nombre' => $moduloData['nombre']],
                    $moduloData
                );
                $modulosCreados[$modulo->nombre] = $modulo;
            }

            echo "✓ Módulos creados\n";

            // 3. CREAR PERMISOS (Módulo + Acción)
            $permisosDefinidos = [
                // Dashboard
                'dashboard' => ['ver'],

                // Periodos
                'periodos' => ['crear', 'ver', 'editar', 'eliminar', 'gestionar_estado'],

                // Materias
                'materias' => ['crear', 'ver', 'editar', 'eliminar', 'exportar'],

                // Aulas
                'aulas' => ['crear', 'ver', 'editar', 'eliminar'],

                // Grupos
                'grupos' => ['crear', 'ver', 'editar', 'eliminar'],

                // Docentes
                'docentes' => ['crear', 'ver', 'editar', 'eliminar', 'gestionar_estado'],

                // Horarios
                'horarios' => ['crear', 'ver', 'editar', 'eliminar', 'exportar'],

                // Carreras
                'carreras' => ['crear', 'ver', 'editar', 'eliminar'],

                // Bitácora
                'bitacora' => ['ver', 'exportar'],

                // Roles (RBAC)
                'roles' => ['crear', 'ver', 'editar', 'eliminar'],

                // Usuarios
                'usuarios' => ['crear', 'ver', 'editar', 'eliminar', 'gestionar_estado'],
            ];

            $permisosCreados = [];
            foreach ($permisosDefinidos as $nombreModulo => $accionesPermitidas) {
                $modulo = $modulosCreados[$nombreModulo];

                foreach ($accionesPermitidas as $nombreAccion) {
                    $accion = Accion::where('nombre', $nombreAccion)->first();

                    if ($accion) {
                        $nombrePermiso = $nombreModulo . '.' . $nombreAccion;
                        $permiso = Permiso::firstOrCreate(
                            ['nombre' => $nombrePermiso],
                            [
                                'id_modulo' => $modulo->id,
                                'id_accion' => $accion->id,
                                'descripcion' => ucfirst($nombreAccion) . ' en ' . $modulo->descripcion,
                            ]
                        );
                        $permisosCreados[$nombrePermiso] = $permiso;
                    }
                }
            }

            echo "✓ Permisos creados (" . count($permisosCreados) . " permisos)\n";

            // 4. CREAR ROLES DEL SISTEMA
            $rolesDefinidos = [
                [
                    'nombre' => 'Super Administrador',
                    'descripcion' => 'Acceso total al sistema',
                    'es_sistema' => true,
                    'permisos' => 'todos', // Todos los permisos
                ],
                [
                    'nombre' => 'Administrador',
                    'descripcion' => 'Administrador del sistema con permisos completos',
                    'es_sistema' => true,
                    'permisos' => 'todos',
                ],
                [
                    'nombre' => 'Coordinador',
                    'descripcion' => 'Coordinador académico con permisos de gestión',
                    'es_sistema' => true,
                    'permisos' => [
                        'dashboard.ver',
                        'periodos.ver', 'periodos.crear', 'periodos.editar',
                        'materias.ver', 'materias.crear', 'materias.editar',
                        'aulas.ver', 'aulas.crear', 'aulas.editar',
                        'grupos.ver', 'grupos.crear', 'grupos.editar', 'grupos.eliminar',
                        'docentes.ver',
                        'horarios.ver', 'horarios.crear', 'horarios.editar', 'horarios.exportar',
                        'carreras.ver',
                        'bitacora.ver',
                    ],
                ],
                [
                    'nombre' => 'Autoridad',
                    'descripcion' => 'Autoridad académica con acceso de lectura',
                    'es_sistema' => true,
                    'permisos' => [
                        'dashboard.ver',
                        'periodos.ver',
                        'materias.ver', 'materias.exportar',
                        'aulas.ver',
                        'grupos.ver',
                        'docentes.ver',
                        'horarios.ver', 'horarios.exportar',
                        'carreras.ver',
                        'bitacora.ver', 'bitacora.exportar',
                    ],
                ],
                [
                    'nombre' => 'Docente',
                    'descripcion' => 'Docente con acceso limitado',
                    'es_sistema' => true,
                    'permisos' => [
                        'dashboard.ver',
                        'horarios.ver',
                        'grupos.ver',
                    ],
                ],
            ];

            foreach ($rolesDefinidos as $rolData) {
                $permisos = $rolData['permisos'];
                unset($rolData['permisos']);

                $rol = Rol::firstOrCreate(
                    ['nombre' => $rolData['nombre']],
                    $rolData
                );

                // Asignar permisos
                if ($permisos === 'todos') {
                    // Asignar todos los permisos
                    $rol->permisos()->sync(Permiso::all()->pluck('id'));
                } else {
                    // Asignar permisos específicos
                    $permisosIds = [];
                    foreach ($permisos as $nombrePermiso) {
                        if (isset($permisosCreados[$nombrePermiso])) {
                            $permisosIds[] = $permisosCreados[$nombrePermiso]->id;
                        }
                    }
                    $rol->permisos()->sync($permisosIds);
                }

                echo "✓ Rol creado: {$rol->nombre} con " . count($rol->permisos) . " permisos\n";
            }

            // 5. ASIGNAR ROL AL USUARIO ADMIN EXISTENTE
            $adminUser = User::where('rol', 'admin')->first();
            if ($adminUser) {
                $rolSuperAdmin = Rol::where('nombre', 'Super Administrador')->first();
                if ($rolSuperAdmin) {
                    $adminUser->roles()->syncWithoutDetaching([
                        $rolSuperAdmin->id => [
                            'asignado_en' => now(),
                            'asignado_por' => $adminUser->id,
                        ]
                    ]);
                    echo "✓ Rol 'Super Administrador' asignado al usuario admin\n";
                }
            }

            DB::commit();
            echo "\n=== SEEDER RBAC COMPLETADO EXITOSAMENTE ===\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "\n❌ ERROR: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
