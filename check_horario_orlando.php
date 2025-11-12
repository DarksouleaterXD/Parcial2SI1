<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICANDO HORARIO DE ORLANDO ===\n\n";

// Obtener docente
$docente = DB::table('docentes')->where('id_persona', 17)->first();
if (!$docente) {
    echo "❌ Docente no encontrado\n";
    exit(1);
}
echo "✅ Docente ID: {$docente->id}\n";

// Obtener periodo vigente
$periodo = DB::table('periodos')->where('vigente', true)->first();
if (!$periodo) {
    $periodo = DB::table('periodos')->orderBy('id', 'desc')->first();
}
echo "✅ Periodo: {$periodo->nombre} (ID: {$periodo->id})\n\n";

// Obtener horarios del docente
$horarios = DB::table('horarios')
    ->where('id_docente', $docente->id)
    ->get();

echo "📅 HORARIOS ENCONTRADOS: " . $horarios->count() . "\n\n";

if ($horarios->isEmpty()) {
    echo "❌ NO HAY HORARIOS ASIGNADOS\n";
    exit(1);
}

foreach ($horarios as $horario) {
    // Obtener grupo
    $grupo = DB::table('grupos')
        ->join('materias', 'grupos.id_materia', '=', 'materias.id')
        ->where('grupos.id', $horario->id_grupo)
        ->select('grupos.*', 'materias.nombre as materia_nombre')
        ->first();
    
    // Obtener aula
    $aula = DB::table('aulas')->where('id', $horario->id_aula)->first();
    
    // Obtener bloque
    $bloque = DB::table('bloques_horarios')->where('id', $horario->id_bloque)->first();
    
    // Decodificar días
    $dias = is_string($horario->dias_semana) ? json_decode($horario->dias_semana, true) : $horario->dias_semana;
    $diasStr = is_array($dias) ? implode(', ', $dias) : 'N/A';
    
    echo "Horario ID {$horario->id}:\n";
    echo "  - Materia: " . ($grupo->materia_nombre ?? 'N/A') . "\n";
    echo "  - Grupo: {$horario->id_grupo} (Periodo: " . ($grupo->id_periodo ?? 'N/A') . ")\n";
    echo "  - Aula: " . ($aula->nombre ?? 'N/A') . "\n";
    echo "  - Bloque: {$bloque->numero_bloque} ({$bloque->hora_inicio} - {$bloque->hora_fin})\n";
    echo "  - Días: {$diasStr}\n";
    echo "  - Activo: " . ($horario->activo ? 'Sí' : 'No') . "\n";
    echo "\n";
}

// Verificar si los grupos están en el periodo correcto
echo "\n🔍 VERIFICANDO GRUPOS:\n\n";
$gruposDocente = DB::table('grupos')
    ->join('materias', 'grupos.id_materia', '=', 'materias.id')
    ->where('grupos.id_docente', $docente->id)
    ->select('grupos.*', 'materias.nombre as materia_nombre')
    ->get();

foreach ($gruposDocente as $grupo) {
    echo "Grupo ID {$grupo->id}:\n";
    echo "  - Materia: {$grupo->materia_nombre}\n";
    echo "  - Periodo: {$grupo->id_periodo}\n";
    echo "  - Paralelo: {$grupo->paralelo}\n";
    $horariosGrupo = DB::table('horarios')->where('id_grupo', $grupo->id)->count();
    echo "  - Horarios: {$horariosGrupo}\n";
    
    // Verificar si el grupo está en el periodo vigente
    if ($grupo->id_periodo == $periodo->id) {
        echo "  ✅ ESTÁ EN PERIODO VIGENTE\n";
    } else {
        echo "  ❌ NO ESTÁ EN PERIODO VIGENTE\n";
    }
    echo "\n";
}

echo "\n=== FIN VERIFICACIÓN ===\n";
