<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Docente;
use App\Models\Periodo;
use App\Models\Horario;
use App\Models\BloqueHorario;

echo "=== SIMULANDO ENDPOINT /mi-horario ===\n\n";

// Buscar docente por id_persona
$docente = Docente::where('id_persona', 17)->first();

if (!$docente) {
    echo "❌ Docente no encontrado\n";
    exit(1);
}

echo "✅ Docente encontrado: ID {$docente->id}\n";

// Buscar periodo vigente
$periodo = Periodo::where('vigente', true)->first();
if (!$periodo) {
    $periodo = Periodo::orderBy('id', 'desc')->first();
}

echo "✅ Periodo: {$periodo->nombre} (ID: {$periodo->id})\n\n";

// Obtener horarios
echo "🔍 Método 1: Con whereHas\n";
$horarios1 = Horario::where('id_docente', $docente->id)
    ->with(['grupo.materia', 'aula', 'bloque'])
    ->whereHas('grupo', function ($query) use ($periodo) {
        $query->where('id_periodo', $periodo->id);
    })
    ->get();

echo "Horarios encontrados con whereHas: " . $horarios1->count() . "\n\n";

echo "� Método 2: Sin whereHas, filtrar después\n";
$horarios2 = Horario::where('id_docente', $docente->id)
    ->with(['grupo.materia', 'aula', 'bloque'])
    ->get();

echo "Horarios totales: " . $horarios2->count() . "\n";

// Filtrar manualmente
$horariosFiltrados = $horarios2->filter(function($h) use ($periodo) {
    return $h->grupo && $h->grupo->id_periodo == $periodo->id;
});

echo "Horarios filtrados manualmente: " . $horariosFiltrados->count() . "\n\n";

// Usar el método que funciona
$horarios = $horariosFiltrados;

echo "📅 Horarios finales: " . $horarios->count() . "\n\n";

if ($horarios->isEmpty()) {
    echo "❌ Sin horarios para este periodo\n";
    exit(1);
}

// Mostrar horarios con sus relaciones cargadas
foreach ($horarios as $h) {
    echo "Horario ID {$h->id}:\n";
    echo "  - dias_semana (raw): " . $h->dias_semana . "\n";
    
    $dias = is_string($h->dias_semana) ? json_decode($h->dias_semana, true) : $h->dias_semana;
    echo "  - dias_semana (decoded): " . (is_array($dias) ? implode(', ', $dias) : 'ERROR') . "\n";
    
    echo "  - Bloque: {$h->id_bloque} - " . ($h->bloque->nombre ?? 'N/A') . "\n";
    echo "  - Grupo: {$h->id_grupo} - " . ($h->grupo->id ?? 'N/A') . "\n";
    echo "  - Materia: " . ($h->grupo->materia->nombre ?? 'N/A') . "\n";
    echo "  - Aula: " . ($h->aula->nombre ?? 'N/A') . "\n";
    echo "\n";
}

// Obtener bloques horarios
$bloques = BloqueHorario::orderBy('numero_bloque')->get();
echo "\n📚 Bloques horarios: " . $bloques->count() . "\n\n";

// Construir grilla (simulando la lógica del controlador)
$diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
$grilla = [];

foreach ($bloques as $bloque) {
    $fila = [
        'bloque_id' => $bloque->id,
        'bloque_numero' => $bloque->numero_bloque,
        'bloque_nombre' => $bloque->nombre,
        'hora_inicio' => $bloque->hora_inicio,
        'hora_fin' => $bloque->hora_fin,
    ];

    foreach ($diasSemana as $dia) {
        // Buscar horario para este bloque y día
        $horario = $horarios->first(function ($h) use ($dia, $bloque) {
            $dias = is_string($h->dias_semana) ? json_decode($h->dias_semana, true) : $h->dias_semana;
            $match = is_array($dias) && in_array($dia, $dias) && $h->id_bloque == $bloque->id;
            return $match;
        });

        if ($horario) {
            $fila[$dia] = [
                'id' => $horario->id,
                'grupo' => $horario->grupo->id ?? null,
                'materia' => $horario->grupo->materia->nombre ?? 'Sin materia',
                'aula' => $horario->aula->nombre ?? 'N/A',
                'paralelo' => $horario->grupo->paralelo ?? 'A',
            ];
        } else {
            $fila[$dia] = null;
        }
    }

    $grilla[] = $fila;
}

// Mostrar grilla JSON
echo "\n📊 GRILLA GENERADA (primeras 3 filas):\n\n";
foreach (array_slice($grilla, 0, 3) as $fila) {
    echo "Bloque {$fila['bloque_numero']} ({$fila['hora_inicio']} - {$fila['hora_fin']}):\n";
    foreach ($diasSemana as $dia) {
        if ($fila[$dia] !== null) {
            echo "  - {$dia}: {$fila[$dia]['materia']} (Aula {$fila[$dia]['aula']})\n";
        } else {
            echo "  - {$dia}: —\n";
        }
    }
    echo "\n";
}

echo "\n=== FIN SIMULACIÓN ===\n";
