<?php

/**
 * Script para migrar datos del campo apellido al nuevo formato
 * apellido_paterno y apellido_materno
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Iniciando migración de apellidos...\n\n";

// Obtener todas las personas que tienen apellido pero no apellido_paterno
$personas = DB::table('personas')
    ->whereNotNull('apellido')
    ->where('apellido', '!=', '')
    ->whereNull('apellido_paterno')
    ->get();

echo "Encontradas " . $personas->count() . " personas con apellido antiguo.\n\n";

foreach ($personas as $persona) {
    // Dividir el apellido en palabras
    $apellidos = explode(' ', trim($persona->apellido));

    $apellidoPaterno = $apellidos[0] ?? null;
    $apellidoMaterno = isset($apellidos[1]) ? implode(' ', array_slice($apellidos, 1)) : null;

    DB::table('personas')
        ->where('id', $persona->id)
        ->update([
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'updated_at' => now(),
        ]);

    echo "✓ Persona ID {$persona->id} - {$persona->nombre}: '{$persona->apellido}' → paterno: '{$apellidoPaterno}', materno: '" . ($apellidoMaterno ?? 'null') . "'\n";
}

echo "\n✅ Migración completada!\n";
echo "Total de registros actualizados: " . $personas->count() . "\n";
