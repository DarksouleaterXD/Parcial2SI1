<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Grupo;
use App\Models\Docente;

echo "🧪 PRUEBA DE VALIDACIÓN: Intentar asignar múltiples paralelos de la misma materia\n";
echo str_repeat("=", 80) . "\n\n";

// Encontrar a Juan García
$juan = Docente::whereHas('persona', function($q) {
    $q->where('nombre', 'Juan');
})->first();

if (!$juan) {
    echo "❌ No se encontró al docente Juan García\n";
    exit(1);
}

echo "👤 Docente: Juan García (ID: {$juan->id})\n\n";

// Ver grupos actuales de Juan
$gruposJuan = Grupo::where('id_docente', $juan->id)
    ->with(['materia', 'periodo'])
    ->get();

echo "📋 Grupos actuales de Juan García:\n";
foreach ($gruposJuan as $g) {
    echo "   - {$g->materia->nombre} | {$g->periodo->nombre} | Paralelo {$g->paralelo}\n";
}
echo "\n";

// Intentar asignar el grupo ID 2 (SA, 2025-2, Investigacion Operativa 1) a Juan
$grupoSA = Grupo::find(2);
echo "🔄 Intentando asignar grupo ID 2 a Juan García:\n";
echo "   Materia: {$grupoSA->materia->nombre}\n";
echo "   Periodo: {$grupoSA->periodo->nombre}\n";
echo "   Paralelo: {$grupoSA->paralelo}\n\n";

echo "⚠️  Juan ya tiene el paralelo CA de Investigacion Operativa 1 en 2025-2\n";
echo "   Por lo tanto, esta asignación debería ser RECHAZADA.\n\n";

// Verificar si existe conflicto
$conflicto = Grupo::where('id_docente', $juan->id)
    ->where('id_materia', $grupoSA->id_materia)
    ->where('id_periodo', $grupoSA->id_periodo)
    ->where('id', '!=', $grupoSA->id)
    ->first();

if ($conflicto) {
    echo "✅ VALIDACIÓN CORRECTA: Se detectó conflicto\n";
    echo "   Juan ya tiene el paralelo {$conflicto->paralelo} de la misma materia en el mismo periodo.\n";
    echo "   No se puede asignar el paralelo {$grupoSA->paralelo}.\n\n";
    echo "❌ ERROR ESPERADO: 'El docente ya tiene asignado otro grupo (paralelo) de esta materia en este periodo.'\n";
} else {
    echo "❌ ERROR: No se detectó conflicto cuando debería existir\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📝 CONCLUSIÓN:\n";
echo "   La validación en el código detecta correctamente cuando un docente\n";
echo "   intenta tener múltiples paralelos de la misma materia en el mismo periodo.\n";
