<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Horario;

echo "=== HORARIOS DE INVESTIGACIÓN OPERATIVA ===\n\n";

$horarios = Horario::with(['grupo.materia', 'bloque', 'docente.persona'])
    ->whereHas('grupo.materia', function($q) {
        $q->where('nombre', 'like', 'Investigacion%');
    })
    ->get();

echo "Total: {$horarios->count()}\n\n";

foreach ($horarios as $h) {
    echo "ID: {$h->id}\n";
    echo "  Materia: " . ($h->grupo->materia->nombre ?? 'N/A') . "\n";
    echo "  Bloque: " . ($h->bloque->nombre ?? 'N/A') . "\n";
    echo "  Docente: " . ($h->docente->persona->nombre ?? 'N/A') . "\n";
    echo "  Días: " . implode(', ', $h->dias_semana ?? []) . "\n";
    echo "  Cantidad de días: " . count($h->dias_semana ?? []) . "\n";
    echo "\n";
}

echo "✅ Este ES el formato correcto:\n";
echo "   - Un registro con MÚLTIPLES días en el array dias_semana\n";
echo "   - Ejemplo: [\"lunes\", \"miercoles\", \"viernes\"]\n";
