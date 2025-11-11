<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "\n=== VERIFICACIÓN DE USUARIOS COORDINADORES ===\n\n";

// Buscar usuarios coordinadores
$coordinadores = User::where('rol', 'coordinador')->with('persona')->get();

if ($coordinadores->isEmpty()) {
    echo "❌ NO HAY USUARIOS CON ROL 'coordinador'\n\n";
    
    // Mostrar todos los usuarios
    echo "Usuarios existentes:\n";
    $usuarios = User::with('persona')->get();
    foreach ($usuarios as $user) {
        echo "  ID: {$user->id} | Email: {$user->email} | Rol: {$user->rol} | Activo: " . ($user->activo ? 'Sí' : 'No') . "\n";
    }
} else {
    echo "✅ Usuarios coordinadores encontrados:\n\n";
    foreach ($coordinadores as $coordinador) {
        echo "ID: {$coordinador->id}\n";
        echo "Email: {$coordinador->email}\n";
        echo "Nombre: {$coordinador->nombre}\n";
        echo "Rol: {$coordinador->rol}\n";
        echo "Activo: " . ($coordinador->activo ? 'Sí' : 'No') . "\n";
        if ($coordinador->persona) {
            echo "Persona: {$coordinador->persona->nombre} {$coordinador->persona->apellido}\n";
        }
        echo "\n";
    }
}

echo "\n=== ROLES DISPONIBLES ===\n";
$roles = User::select('rol')->distinct()->get();
foreach ($roles as $rol) {
    $count = User::where('rol', $rol->rol)->count();
    echo "- {$rol->rol}: {$count} usuario(s)\n";
}

echo "\n";
