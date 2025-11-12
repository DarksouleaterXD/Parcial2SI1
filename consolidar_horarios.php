<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Horario;
use Illuminate\Support\Facades\DB;

echo "=== CONSOLIDACIÓN DE HORARIOS ===\n\n";

// Obtener todos los horarios activos
$horarios = Horario::with(['grupo.materia', 'bloque', 'docente.persona', 'aula'])
    ->get();

echo "Total horarios actuales: {$horarios->count()}\n\n";

// Agrupar horarios por: grupo, bloque, docente, aula (mismas características)
$grupos = [];

foreach ($horarios as $horario) {
    $key = sprintf(
        'G%d-B%d-D%d-A%d',
        $horario->id_grupo,
        $horario->id_bloque ?? 0,
        $horario->id_docente,
        $horario->id_aula
    );
    
    if (!isset($grupos[$key])) {
        $grupos[$key] = [
            'horarios' => [],
            'dias' => [],
            'info' => [
                'grupo' => $horario->grupo->codigo ?? 'N/A',
                'materia' => $horario->grupo->materia->nombre ?? 'N/A',
                'bloque' => $horario->bloque->nombre ?? 'N/A',
                'docente' => ($horario->docente->persona->nombre ?? '') . ' ' . ($horario->docente->persona->apellido_paterno ?? ''),
                'aula' => $horario->aula->numero_aula ?? 'N/A',
            ]
        ];
    }
    
    $grupos[$key]['horarios'][] = $horario;
    
    // Agregar días de este horario
    if ($horario->dias_semana && is_array($horario->dias_semana)) {
        foreach ($horario->dias_semana as $dia) {
            if (!in_array($dia, $grupos[$key]['dias'])) {
                $grupos[$key]['dias'][] = $dia;
            }
        }
    }
}

echo "Grupos de horarios encontrados: " . count($grupos) . "\n\n";

// Mostrar horarios que pueden consolidarse
$consolidables = 0;

foreach ($grupos as $key => $grupo) {
    if (count($grupo['horarios']) > 1) {
        $consolidables++;
        echo "📦 Grupo consolidable:\n";
        echo "   Materia: {$grupo['info']['materia']}\n";
        echo "   Grupo: {$grupo['info']['grupo']}\n";
        echo "   Bloque: {$grupo['info']['bloque']}\n";
        echo "   Docente: {$grupo['info']['docente']}\n";
        echo "   Aula: {$grupo['info']['aula']}\n";
        echo "   Horarios individuales: " . count($grupo['horarios']) . "\n";
        echo "   Días: " . implode(', ', $grupo['dias']) . "\n";
        echo "   IDs a eliminar: " . implode(', ', array_map(fn($h) => $h->id, array_slice($grupo['horarios'], 1))) . "\n";
        echo "\n";
    }
}

if ($consolidables === 0) {
    echo "✅ No hay horarios para consolidar. Todos están correctamente agrupados.\n";
    exit(0);
}

echo "\n¿Desea consolidar estos horarios? (s/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 's') {
    echo "❌ Operación cancelada.\n";
    exit(0);
}

echo "\n🔄 Consolidando horarios...\n\n";

DB::beginTransaction();

try {
    $consolidados = 0;
    $eliminados = 0;
    
    foreach ($grupos as $key => $grupo) {
        if (count($grupo['horarios']) <= 1) continue;
        
        // Tomar el primer horario como base
        $horarioBase = $grupo['horarios'][0];
        
        // Actualizar con todos los días
        $horarioBase->dias_semana = $grupo['dias'];
        $horarioBase->save();
        
        echo "✅ Consolidado horario ID {$horarioBase->id}: " . implode(', ', $grupo['dias']) . "\n";
        $consolidados++;
        
        // Eliminar los demás horarios
        for ($i = 1; $i < count($grupo['horarios']); $i++) {
            $horarioEliminar = $grupo['horarios'][$i];
            $horarioEliminar->delete();
            echo "   🗑️  Eliminado horario ID {$horarioEliminar->id}\n";
            $eliminados++;
        }
        
        echo "\n";
    }
    
    DB::commit();
    
    echo "\n✅ CONSOLIDACIÓN COMPLETADA\n";
    echo "   - Horarios consolidados: {$consolidados}\n";
    echo "   - Horarios eliminados: {$eliminados}\n";
    echo "   - Horarios finales: " . Horario::count() . "\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Se revirtieron todos los cambios.\n";
}

echo "\n=== FIN CONSOLIDACIÓN ===\n";
