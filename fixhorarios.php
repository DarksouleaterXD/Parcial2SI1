<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Ver todos los IDs
$horarios = App\Models\Horario::all();
echo "Horarios disponibles:\n";
foreach ($horarios as $h) {
    echo "  ID: {$h->id}, Grupo: {$h->id_grupo}, Días: " . json_encode($h->dias_semana) . "\n";
}

// Limpiar el primer horario
if ($horarios->count() > 0) {
    $primer = $horarios->first();
    echo "\nLimpiando horario ID {$primer->id}...\n";
    $primer->dias_semana = ['Lunes', 'Miércoles', 'Viernes'];
    $primer->save();
    echo "Limpiado: " . json_encode($primer->dias_semana) . "\n";
}
