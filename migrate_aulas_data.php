<?php

/**
 * Script para migrar datos existentes de aulas
 * Extrae el número de aula del código actual y lo asigna al campo numero_aula
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Aulas;

echo "=== MIGRACIÓN DE DATOS: numero_aula ===\n\n";

$aulas = Aulas::all();

echo "Total de aulas encontradas: " . $aulas->count() . "\n\n";

foreach ($aulas as $aula) {
    // Extraer número del código
    // Ejemplos: "AULA_1" -> 1, "AULA 1" -> 1, "A101" -> 101, "LAB-02" -> 2
    $codigo = $aula->codigo;

    // Intentar extraer el número
    preg_match('/(\d+)/', $codigo, $matches);

    if (!empty($matches[1])) {
        $numeroAula = (int) $matches[1];

        // Si el número es muy pequeño (1-9), probablemente sea un ID simple
        // Asignar un número de aula razonable
        if ($numeroAula < 10) {
            $numeroAula = 10 + $numeroAula; // 1 -> 11, 2 -> 12, etc.
        }

        echo "Aula ID {$aula->id}: Código '{$codigo}' -> Número de aula: {$numeroAula}\n";

        $aula->numero_aula = $numeroAula;
        $aula->save();
    } else {
        echo "⚠ Aula ID {$aula->id}: No se pudo extraer número del código '{$codigo}'\n";
    }
}

echo "\n=== MIGRACIÓN COMPLETADA ===\n";
