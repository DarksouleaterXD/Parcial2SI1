<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Ver todos los horarios
$horarios = App\Models\Horario::all();

foreach ($horarios as $horario) {
    echo "Horario #{$horario->id}:\n";
    echo "  Días: " . json_encode($horario->dias_semana) . "\n";
    echo "  Total: " . count($horario->dias_semana) . " días\n\n";
}
