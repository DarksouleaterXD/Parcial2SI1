<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Limpiar el horario #1 a días correctos
$h = App\Models\Horario::find(1);
if ($h) {
    $h->dias_semana = ['Lunes', 'Miércoles', 'Viernes'];
    $h->save();
    echo "Horario #1 limpiado: " . json_encode($h->dias_semana) . "\n";
} else {
    echo "Horario #1 no encontrado\n";
}
