<?php

/**
 * Script para completar apellidos faltantes en personas
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "COMPLETAR APELLIDOS FALTANTES\n";
echo "========================================\n\n";

// Buscar personas sin apellido_paterno que tienen usuario
$personas = DB::table('personas')
    ->whereNull('apellido_paterno')
    ->orWhere('apellido_paterno', '')
    ->get();

echo "Personas sin apellido_paterno: " . $personas->count() . "\n\n";

$actualizados = 0;

foreach ($personas as $persona) {
    // Intentar obtener el nombre completo del usuario
    $usuario = DB::table('usuarios')->find($persona->id_usuario);

    if ($usuario) {
        // Dividir el nombre completo
        $partesNombre = explode(' ', trim($usuario->nombre));

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

        echo "✓ Persona ID {$persona->id}: '{$usuario->nombre}' → nombre: '{$nombre}', paterno: '{$apellidoPaterno}', materno: '{$apellidoMaterno}'\n";
        $actualizados++;
    } else {
        echo "⚠️  Persona ID {$persona->id} sin usuario asociado\n";
    }
}

echo "\n========================================\n";
echo "Total actualizados: {$actualizados}\n";
echo "========================================\n";
