<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Limpiar todos los horarios con días duplicados
$horarios = App\Models\Horario::all();

foreach ($horarios as $horario) {
    // Eliminar duplicados manteniendo el orden
    $diasUnicos = array_values(array_unique($horario->dias_semana));

    if (count($diasUnicos) !== count($horario->dias_semana)) {
        echo "Horario #{$horario->id}: " . count($horario->dias_semana) . " días -> " . count($diasUnicos) . " días únicos\n";
        echo "  Antes: " . json_encode($horario->dias_semana) . "\n";
        $horario->dias_semana = $diasUnicos;
        $horario->save();
        echo "  Después: " . json_encode($horario->dias_semana) . "\n\n";
    }
}

echo "Limpieza completada.\n";
