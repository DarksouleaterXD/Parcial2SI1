<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Horario;
use App\Models\BloqueHorario;

echo "=== CREAR HORARIO CONSOLIDADO LUN/MIÉ/VIE ===\n\n";

// Paso 1: Eliminar horarios separados de Cálculo 1 (IDs 2, 3, 4)
echo "1. Eliminando horarios separados de Cálculo 1...\n";
$idsEliminar = [2, 3, 4];
foreach ($idsEliminar as $id) {
    $horario = Horario::find($id);
    if ($horario) {
        $horario->delete();
        echo "   ✅ Eliminado horario ID {$id}\n";
    }
}

// Paso 2: Crear horario consolidado Lunes/Miércoles/Viernes
echo "\n2. Creando horario consolidado...\n";

$bloque1 = BloqueHorario::where('nombre', 'like', 'Bloque 1%')->first();

if (!$bloque1) {
    echo "   ❌ Bloque 1 no encontrado\n";
    exit;
}

$nuevoHorario = Horario::create([
    'id_grupo' => 7, // Grupo MAT101-A (Cálculo 1)
    'id_aula' => 1,
    'id_docente' => 7, // Juan Pérez
    'id_bloque' => $bloque1->id,
    'dias_semana' => ['Lunes', 'Miércoles', 'Viernes'],
    'activo' => true,
    'descripcion' => 'Cálculo 1 - LUN/MIÉ/VIE'
]);

echo "   ✅ Horario consolidado creado\n";
echo "   - ID: {$nuevoHorario->id}\n";
echo "   - Días: Lunes, Miércoles, Viernes\n";
echo "   - Horario: {$bloque1->hora_inicio} - {$bloque1->hora_fin}\n";

// Paso 3: Verificar y restaurar Investigación Operativa
echo "\n3. Verificando Investigación Operativa...\n";

$horarioIO = Horario::withTrashed()->find(6);

if ($horarioIO) {
    if ($horarioIO->trashed()) {
        $horarioIO->restore();
        echo "   ✅ Horario de Investigación Operativa restaurado (ID: 6)\n";
    } else {
        echo "   ✅ Horario de Investigación Operativa ya está activo (ID: 6)\n";
    }
} else {
    echo "   ❌ Horario ID 6 no encontrado\n";
}

// Paso 4: Verificación final
echo "\n4. HORARIOS FINALES DEL DOCENTE JUAN PÉREZ:\n\n";

$horariosFinales = Horario::where('id_docente', 7)
    ->with(['grupo.materia', 'bloque'])
    ->get();

foreach ($horariosFinales as $h) {
    $materia = $h->grupo->materia->nombre ?? 'N/A';
    $bloque = $h->bloque->nombre ?? 'N/A';
    $inicio = $h->bloque->hora_inicio ?? '';
    $fin = $h->bloque->hora_fin ?? '';
    $dias = is_array($h->dias_semana) ? $h->dias_semana : json_decode($h->dias_semana, true);
    
    // Formatear horas
    $horaInicio = date('H:i', strtotime($inicio));
    $horaFin = date('H:i', strtotime($fin));
    
    echo "✅ ID: {$h->id} | {$materia}\n";
    echo "   Bloque: {$bloque} ({$horaInicio} - {$horaFin})\n";
    echo "   Días: " . implode(', ', $dias) . "\n\n";
}

echo "=== FIN ===\n";
