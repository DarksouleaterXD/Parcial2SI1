<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== VERIFICAR AUTORIDADES ===\n\n";

// Buscar autoridades por columna rol
$autoridadesColumna = User::where('rol', 'autoridad')->get();

echo "AUTORIDADES (por columna rol):\n";
if ($autoridadesColumna->isEmpty()) {
    echo "No hay usuarios con rol='autoridad' en la columna\n\n";
} else {
    foreach($autoridadesColumna as $aut) {
        echo "ID: {$aut->id} - {$aut->nombre} - {$aut->email}\n";
        echo "  Columna rol: {$aut->rol}\n";
        echo "  Roles RBAC: ";
        foreach($aut->roles as $rol) {
            echo "{$rol->nombre}, ";
        }
        echo "\n\n";
    }
}

// Buscar autoridades por RBAC
$autoridadesRBAC = User::whereHas('roles', function($q) {
    $q->where('nombre', 'Autoridad');
})->get();

echo "AUTORIDADES (por RBAC):\n";
if ($autoridadesRBAC->isEmpty()) {
    echo "No hay usuarios con rol RBAC 'Autoridad'\n\n";
} else {
    foreach($autoridadesRBAC as $aut) {
        echo "ID: {$aut->id} - {$aut->nombre} - {$aut->email}\n";
        echo "  Columna rol: {$aut->rol}\n";
        echo "  Roles RBAC: ";
        foreach($aut->roles as $rol) {
            echo "{$rol->nombre}, ";
        }
        echo "\n";
        echo "  roles()->where('nombre', 'Autoridad')->exists(): " . ($aut->roles()->where('nombre', 'Autoridad')->exists() ? 'SI' : 'NO') . "\n\n";
    }
}
