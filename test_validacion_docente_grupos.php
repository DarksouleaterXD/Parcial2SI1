<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Grupo;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Periodo;

echo "🔍 VERIFICACIÓN DE REGLA DE NEGOCIO: Docente - Grupos\n";
echo "=" . str_repeat("=", 70) . "\n\n";

echo "📋 REGLA: Un docente puede enseñar VARIAS MATERIAS,\n";
echo "          pero NO puede tener MÚLTIPLES PARALELOS de la MISMA MATERIA\n\n";

// Obtener todos los grupos con docente asignado
$grupos = Grupo::with(['materia', 'periodo', 'docente.persona'])
    ->whereNotNull('id_docente')
    ->orderBy('id_docente')
    ->orderBy('id_materia')
    ->get();

echo "📊 Total de grupos con docente asignado: " . $grupos->count() . "\n\n";

// Agrupar por docente
$gruposPorDocente = [];
foreach ($grupos as $grupo) {
    $id_docente = $grupo->id_docente;
    if (!isset($gruposPorDocente[$id_docente])) {
        $gruposPorDocente[$id_docente] = [
            'nombre' => $grupo->docente->persona->nombre . ' ' .
                       ($grupo->docente->persona->apellido_paterno ?? '') . ' ' .
                       ($grupo->docente->persona->apellido_materno ?? ''),
            'grupos' => []
        ];
    }
    $gruposPorDocente[$id_docente]['grupos'][] = $grupo;
}

// Verificar cada docente
$errores = [];
foreach ($gruposPorDocente as $id_docente => $data) {
    echo "👤 Docente ID {$id_docente}: {$data['nombre']}\n";
    echo str_repeat("-", 70) . "\n";

    // Agrupar por materia+periodo
    $materiaPeriodo = [];
    foreach ($data['grupos'] as $grupo) {
        $key = "{$grupo->id_materia}_{$grupo->id_periodo}";
        if (!isset($materiaPeriodo[$key])) {
            $materiaPeriodo[$key] = [
                'materia' => $grupo->materia->nombre,
                'periodo' => $grupo->periodo->nombre,
                'paralelos' => []
            ];
        }
        $materiaPeriodo[$key]['paralelos'][] = $grupo->paralelo;
    }

    // Mostrar materias y verificar duplicados
    foreach ($materiaPeriodo as $key => $info) {
        $count = count($info['paralelos']);
        $paralelos_str = implode(', ', $info['paralelos']);

        if ($count > 1) {
            echo "   ❌ ERROR: {$info['materia']} ({$info['periodo']}) - {$count} paralelos: [{$paralelos_str}]\n";
            $errores[] = [
                'docente' => $data['nombre'],
                'materia' => $info['materia'],
                'periodo' => $info['periodo'],
                'paralelos' => $info['paralelos']
            ];
        } else {
            echo "   ✅ OK: {$info['materia']} ({$info['periodo']}) - Paralelo: {$paralelos_str}\n";
        }
    }
    echo "\n";
}

// Resumen final
echo "\n" . str_repeat("=", 70) . "\n";
echo "📊 RESUMEN FINAL\n";
echo str_repeat("=", 70) . "\n\n";

if (count($errores) > 0) {
    echo "❌ SE ENCONTRARON " . count($errores) . " VIOLACIONES A LA REGLA DE NEGOCIO:\n\n";
    foreach ($errores as $i => $error) {
        echo ($i + 1) . ". Docente: {$error['docente']}\n";
        echo "   Materia: {$error['materia']}\n";
        echo "   Periodo: {$error['periodo']}\n";
        echo "   Paralelos: " . implode(', ', $error['paralelos']) . "\n\n";
    }

    echo "\n⚠️  ACCIÓN REQUERIDA:\n";
    echo "   Estos grupos deben ser reasignados a diferentes docentes.\n";
    echo "   Cada docente solo debe tener UN paralelo por materia en cada periodo.\n\n";
} else {
    echo "✅ ¡PERFECTO! Todos los grupos cumplen con la regla de negocio.\n";
    echo "   Cada docente tiene máximo un paralelo por materia en cada periodo.\n\n";
}

echo "💡 RECORDATORIO:\n";
echo "   ✅ PERMITIDO: Un docente puede enseñar Matemáticas I, Física II, Química III (diferentes materias)\n";
echo "   ❌ PROHIBIDO: Un docente puede enseñar Matemáticas I paralelo A y paralelo B (misma materia)\n\n";
