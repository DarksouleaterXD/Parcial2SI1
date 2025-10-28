<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Docente;
use App\Models\User;

// Obtener el primer docente
$docente = Docente::with('persona')->first();
$user = User::where('email', 'admin@gestion.com')->first();

if (!$docente || !$user) {
    echo "❌ No hay docentes o usuario admin\n";
    exit(1);
}

// Simular request
echo "Probando cambio de estado...\n";
echo "Docente ID: {$docente->id}\n";
echo "Usuario ID: {$user->id}\n";
echo "Estado actual: " . ($docente->activo ? 'activo' : 'inactivo') . "\n";

// Intentar cambiar estado
try {
    $nuevoEstado = !$docente->activo;
    $docente->update(['activo' => $nuevoEstado]);

    echo "✅ Estado actualizado a: " . ($nuevoEstado ? 'activo' : 'inactivo') . "\n";

    // Intentar guardar en bitácora
    \App\Models\Bitacora::create([
        'id_usuario' => $user->id,
        'tabla' => 'docentes',
        'operacion' => 'cambiar_estado',
        'id_registro' => $docente->id,
        'descripcion' => "Estado cambió a " . ($nuevoEstado ? 'activo' : 'inactivo'),
    ]);

    echo "✅ Bitácora registrada\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
