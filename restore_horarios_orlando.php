<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Horario;

echo "=== RESTAURANDO HORARIOS DE ORLANDO ===\n\n";

// Encontrar horarios eliminados del docente 7
$horariosEliminados = Horario::onlyTrashed()->where('id_docente', 7)->get();

echo "Horarios eliminados encontrados: " . $horariosEliminados->count() . "\n\n";

foreach ($horariosEliminados as $h) {
    echo "Restaurando horario ID {$h->id}...\n";
    $h->restore();
    echo "✅ Restaurado\n\n";
}

// Verificar
$horariosActivos = Horario::where('id_docente', 7)->get();
echo "\nHorarios activos ahora: " . $horariosActivos->count() . "\n";

echo "\n=== FIN RESTAURACIÓN ===\n";
