<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Horario;

echo "=== RESTAURACIÓN DE HORARIOS DE CÁLCULO 1 ===\n\n";

// IDs de los horarios eliminados de Cálculo 1
$horariosEliminados = [2, 3, 4];

foreach ($horariosEliminados as $id) {
    $horario = Horario::withTrashed()->find($id);
    
    if ($horario) {
        if ($horario->trashed()) {
            $horario->restore();
            echo "✅ Horario ID {$id} restaurado exitosamente\n";
            echo "   - Bloque: {$horario->bloque->nombre}\n";
            echo "   - Días: " . implode(', ', is_array($horario->dias_semana) ? $horario->dias_semana : json_decode($horario->dias_semana, true)) . "\n\n";
        } else {
            echo "ℹ️  Horario ID {$id} ya estaba activo\n\n";
        }
    } else {
        echo "❌ Horario ID {$id} no encontrado\n\n";
    }
}

echo "=== VERIFICACIÓN FINAL ===\n\n";

// Verificar horarios activos del docente ID 7
$horariosActivos = Horario::where('id_docente', 7)
    ->with(['bloque', 'grupo.materia'])
    ->get();

echo "Horarios activos del docente Juan Pérez: {$horariosActivos->count()}\n\n";

foreach ($horariosActivos as $h) {
    $materia = $h->grupo->materia->nombre ?? 'N/A';
    $bloque = $h->bloque->nombre ?? 'N/A';
    $dias = is_array($h->dias_semana) ? $h->dias_semana : json_decode($h->dias_semana, true);
    
    echo "- Horario ID: {$h->id}\n";
    echo "  Materia: {$materia}\n";
    echo "  Bloque: {$bloque}\n";
    echo "  Días: " . implode(', ', $dias) . "\n\n";
}

echo "=== FIN RESTAURACIÓN ===\n";
