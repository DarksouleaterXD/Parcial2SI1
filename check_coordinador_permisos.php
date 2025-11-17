<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COORDINADORES EN EL SISTEMA ===\n\n";

$coordinadores = DB::table('usuarios')
    ->where('rol', 'coordinador')
    ->get();

foreach ($coordinadores as $user) {
    echo "ID: {$user->id} - {$user->nombre} ({$user->email})\n";
    echo "  Columna rol: {$user->rol}\n";
    
    // Obtener roles RBAC
    $rolesRBAC = DB::table('usuario_rol')
        ->join('roles', 'usuario_rol.id_rol', '=', 'roles.id')
        ->where('usuario_rol.id_usuario', $user->id)
        ->select('roles.id as rol_id', 'roles.nombre', 'roles.descripcion')
        ->get();
    
    echo "  Roles RBAC: ";
    if ($rolesRBAC->isEmpty()) {
        echo "(ninguno)\n";
    } else {
        echo "\n";
        foreach ($rolesRBAC as $rol) {
            echo "    - {$rol->nombre}";
            if ($rol->descripcion) echo " ({$rol->descripcion})";
            echo "\n";
        }
    }
    echo "\n";
}

echo "\n=== ROL COORDINADOR EN TABLA ROLES ===\n\n";

$rolCoordinador = DB::table('roles')
    ->where('nombre', 'Coordinador')
    ->first();

if ($rolCoordinador) {
    echo "ID: {$rolCoordinador->id}\n";
    echo "Nombre: {$rolCoordinador->nombre}\n";
    echo "Descripción: {$rolCoordinador->descripcion}\n";
    echo "Activo: " . ($rolCoordinador->activo ? 'Sí' : 'No') . "\n\n";
    
    echo "Permisos asignados al rol Coordinador:\n";
    $permisos = DB::table('rol_permiso')
        ->join('permisos', 'rol_permiso.id_permiso', '=', 'permisos.id')
        ->join('acciones', 'permisos.id_accion', '=', 'acciones.id')
        ->join('modulos', 'permisos.id_modulo', '=', 'modulos.id')
        ->where('rol_permiso.id_rol', $rolCoordinador->id)
        ->select('modulos.nombre as modulo', 'acciones.nombre as accion', 'permisos.descripcion')
        ->orderBy('modulos.nombre')
        ->orderBy('acciones.nombre')
        ->get();
    
    if ($permisos->isEmpty()) {
        echo "  (No tiene permisos asignados en RBAC)\n";
    } else {
        $moduloActual = null;
        foreach ($permisos as $permiso) {
            if ($moduloActual !== $permiso->modulo) {
                $moduloActual = $permiso->modulo;
                echo "\n  [$moduloActual]\n";
            }
            echo "    • {$permiso->accion}";
            if ($permiso->descripcion) echo " - {$permiso->descripcion}";
            echo "\n";
        }
    }
} else {
    echo "No se encontró el rol Coordinador en la tabla roles.\n";
}

echo "\n\n=== RUTAS DISPONIBLES PARA COORDINADOR EN api.php ===\n";
echo "(Revisa el archivo routes/api.php para ver las rutas exactas)\n";
