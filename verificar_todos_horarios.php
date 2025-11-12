<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Horario;
use App\Models\Grupo;

echo "=== VERIFICACIÓN DE TODOS LOS HORARIOS (INCLUYENDO ELIMINADOS) ===\n\n";

// Verificar horarios incluyendo soft-deleted
$horariosAll = Horario::withTrashed()
    ->with(['grupo.materia', 'bloque', 'docente.persona'])
    ->get();

echo "Total horarios (incluyendo eliminados): {$horariosAll->count()}\n\n";

foreach ($horariosAll as $h) {
    $deleted = $h->trashed() ? '🗑️ ELIMINADO' : '✅ ACTIVO';
    $materia = $h->grupo->materia->nombre ?? 'N/A';
    $grupoId = $h->id_grupo;
    $grupoPeriodo = $h->grupo->id_periodo ?? 'N/A';
    $docenteId = $h->id_docente;
    $docenteNombre = $h->docente->persona->nombre ?? 'N/A';
    $bloque = $h->bloque->nombre ?? 'N/A';
    $dias = is_array($h->dias_semana) ? $h->dias_semana : (json_decode($h->dias_semana, true) ?? []);
    
    echo "- Horario ID: {$h->id} {$deleted}\n";
    echo "  Materia: {$materia}\n";
    echo "  Grupo ID: {$grupoId} (Periodo: {$grupoPeriodo})\n";
    echo "  Docente: {$docenteNombre} (ID: {$docenteId})\n";
    echo "  Bloque: {$bloque}\n";
    echo "  Días: " . implode(', ', $dias) . "\n";
    echo "\n";
}

// Verificar el grupo MAT101-A
echo "\n=== VERIFICACIÓN DEL GRUPO MAT101-A ===\n\n";
$grupoCalculo = Grupo::where('codigo', 'MAT101-A')->first();
if ($grupoCalculo) {
    echo "✅ Grupo encontrado:\n";
    echo "   ID: {$grupoCalculo->id}\n";
    echo "   Código: {$grupoCalculo->codigo}\n";
    echo "   Periodo ID: {$grupoCalculo->id_periodo}\n";
    echo "   Docente ID: {$grupoCalculo->id_docente}\n";
    echo "   Materia ID: {$grupoCalculo->id_materia}\n";
    
    // Buscar horarios de este grupo
    $horariosGrupo = Horario::withTrashed()
        ->where('id_grupo', $grupoCalculo->id)
        ->get();
    
    echo "\n   Horarios asociados a este grupo: {$horariosGrupo->count()}\n";
    foreach ($horariosGrupo as $hg) {
        $status = $hg->trashed() ? '🗑️ ELIMINADO' : '✅ ACTIVO';
        echo "   - Horario ID: {$hg->id} {$status}, Docente ID: {$hg->id_docente}\n";
    }
} else {
    echo "❌ Grupo MAT101-A no encontrado\n";
}

echo "\n=== FIN VERIFICACIÓN ===\n";
