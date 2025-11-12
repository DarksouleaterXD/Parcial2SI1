<?php

/**
 * Script para vincular personas con usuarios y completar apellidos
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "VINCULAR PERSONAS CON USUARIOS\n";
echo "========================================\n\n";

// Primero, actualizar id_usuario en personas basado en usuarios.id_persona
$usuarios = DB::table('usuarios')->whereNotNull('id_persona')->get();

echo "Vinculando personas con usuarios...\n\n";

foreach ($usuarios as $usuario) {
    $updated = DB::table('personas')
        ->where('id', $usuario->id_persona)
        ->update(['id_usuario' => $usuario->id]);

    if ($updated) {
        echo "✓ Persona ID {$usuario->id_persona} vinculada con Usuario ID {$usuario->id}\n";
    }
}

echo "\n========================================\n";
echo "COMPLETAR APELLIDOS\n";
echo "========================================\n\n";

// Ahora completar apellidos
$personas = DB::table('personas')
    ->join('usuarios', 'personas.id', '=', 'usuarios.id_persona')
    ->whereNull('personas.apellido_paterno')
    ->orWhere('personas.apellido_paterno', '')
    ->select('personas.*', 'usuarios.nombre as nombre_usuario')
    ->get();

echo "Personas a actualizar: " . $personas->count() . "\n\n";

foreach ($personas as $persona) {
    $partesNombre = explode(' ', trim($persona->nombre_usuario));

    $nombre = $partesNombre[0] ?? $persona->nombre;
    $apellidoPaterno = $partesNombre[1] ?? '';
    $apellidoMaterno = isset($partesNombre[2]) ? implode(' ', array_slice($partesNombre, 2)) : '';

    DB::table('personas')
        ->where('id', $persona->id)
        ->update([
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'updated_at' => now(),
        ]);

    echo "✓ Persona ID {$persona->id}: '{$persona->nombre_usuario}' → '{$nombre}' '{$apellidoPaterno}' '{$apellidoMaterno}'\n";
}

echo "\n========================================\n";
echo "COMPLETADO\n";
echo "========================================\n";
