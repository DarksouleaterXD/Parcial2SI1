<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== VERIFICAR ROLES Y PERMISOS ===\n\n";

// Buscar coordinadores
$coordinadores = User::whereHas('roles', function($q) {
    $q->where('nombre', 'Coordinador');
})->get();

echo "COORDINADORES:\n";
foreach($coordinadores as $coord) {
    echo "ID: {$coord->id}\n";
    echo "  Nombre: {$coord->nombre}\n";
    echo "  Email: {$coord->email}\n";
    echo "  Columna rol: {$coord->rol}\n";
    echo "  Roles RBAC: ";
    foreach($coord->roles as $rol) {
        echo "{$rol->nombre}, ";
    }
    echo "\n";
    echo "  tieneRol('Coordinador'): " . ($coord->tieneRol('Coordinador') ? 'SI' : 'NO') . "\n";
    echo "  isCoordinador(): " . ($coord->isCoordinador() ? 'SI' : 'NO') . "\n";
    echo "\n";
}

// Buscar docentes
$docentes = User::whereHas('roles', function($q) {
    $q->where('nombre', 'Docente');
})->limit(3)->get();

echo "\nDOCENTES (primeros 3):\n";
foreach($docentes as $doc) {
    echo "ID: {$doc->id}\n";
    echo "  Nombre: {$doc->nombre}\n";
    echo "  Email: {$doc->email}\n";
    echo "  Columna rol: {$doc->rol}\n";
    echo "  Roles RBAC: ";
    foreach($doc->roles as $rol) {
        echo "{$rol->nombre}, ";
    }
    echo "\n";
    echo "  tieneRol('Docente'): " . ($doc->tieneRol('Docente') ? 'SI' : 'NO') . "\n";
    echo "  isDocente(): " . ($doc->isDocente() ? 'SI' : 'NO') . "\n";
    echo "\n";
}

// Verificar todos los roles
echo "\nROLES EN EL SISTEMA:\n";
$roles = DB::table('roles')->get();
foreach($roles as $rol) {
    echo "- {$rol->nombre} (ID: {$rol->id})\n";
}
