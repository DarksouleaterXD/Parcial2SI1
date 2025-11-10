<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

echo "=== Probando endpoints RBAC sin autenticación ===\n\n";

// Probar GET /api/admin/roles
echo "1. GET /api/admin/roles\n";
$request = \Illuminate\Http\Request::create('/api/admin/roles', 'GET');
$request->headers->set('Accept', 'application/json');
$response = $kernel->handle($request);
echo "Status: " . $response->status() . "\n";
if ($response->status() === 401) {
    echo "✓ Protegido por autenticación (esperado)\n\n";
} else {
    echo "✗ Debería estar protegido\n";
    echo substr($response->getContent(), 0, 200) . "...\n\n";
}

// Probar GET /api/admin/permisos
echo "2. GET /api/admin/permisos\n";
$request = \Illuminate\Http\Request::create('/api/admin/permisos', 'GET');
$request->headers->set('Accept', 'application/json');
$response = $kernel->handle($request);
echo "Status: " . $response->status() . "\n";
if ($response->status() === 401) {
    echo "✓ Protegido por autenticación (esperado)\n\n";
} else {
    echo "✗ Debería estar protegido\n";
    echo substr($response->getContent(), 0, 200) . "...\n\n";
}

// Verificar que los modelos existen
echo "3. Verificando modelos\n";
try {
    $rolesCount = \App\Models\Rol::count();
    echo "✓ Roles en BD: {$rolesCount}\n";

    $permisosCount = \App\Models\Permiso::count();
    echo "✓ Permisos en BD: {$permisosCount}\n";

    $modulosCount = \App\Models\Modulo::count();
    echo "✓ Módulos en BD: {$modulosCount}\n";

    $accionesCount = \App\Models\Accion::count();
    echo "✓ Acciones en BD: {$accionesCount}\n\n";

    // Mostrar un rol de ejemplo
    $rol = \App\Models\Rol::with(['permisos.modulo', 'permisos.accion'])->first();
    if ($rol) {
        echo "Ejemplo de rol:\n";
        echo "- Nombre: {$rol->nombre}\n";
        echo "- Descripción: {$rol->descripcion}\n";
        echo "- Es sistema: " . ($rol->es_sistema ? 'Sí' : 'No') . "\n";
        echo "- Permisos: " . $rol->permisos->count() . "\n";
        if ($rol->permisos->count() > 0) {
            echo "  Primer permiso: {$rol->permisos[0]->modulo->nombre} - {$rol->permisos[0]->accion->nombre}\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Pruebas completadas ===\n";
