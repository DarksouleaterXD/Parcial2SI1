<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Horario;

echo "=== HORARIOS DE CÁLCULO 1 ===\n\n";

$horarios = Horario::with(['grupo.materia', 'bloque', 'docente.persona'])
    ->whereHas('grupo.materia', function($q) {
        $q->where('nombre', 'like', 'Calculo%');
    })
    ->get();

echo "Total: {$horarios->count()}\n\n";

foreach ($horarios as $h) {
    echo "ID: {$h->id}\n";
    echo "  Materia: " . ($h->grupo->materia->nombre ?? 'N/A') . "\n";
    echo "  Grupo ID: {$h->id_grupo}\n";
    echo "  Bloque ID: {$h->id_bloque}\n";
    echo "  Docente ID: {$h->id_docente}\n";
    echo "  Aula ID: {$h->id_aula}\n";
    echo "  Días (JSON): " . json_encode($h->dias_semana) . "\n";
    echo "  Días (tipo): " . gettype($h->dias_semana) . "\n";
    echo "\n";
}

// Verificar si tienen mismo grupo/bloque/docente/aula
echo "=== ANÁLISIS ===\n";
$primerHorario = $horarios->first();
$mismoGrupo = $horarios->every(fn($h) => $h->id_grupo === $primerHorario->id_grupo);
$mismoDocente = $horarios->every(fn($h) => $h->id_docente === $primerHorario->id_docente);
$mismaAula = $horarios->every(fn($h) => $h->id_aula === $primerHorario->id_aula);

echo "¿Mismo grupo? " . ($mismoGrupo ? 'SÍ' : 'NO') . "\n";
echo "¿Mismo docente? " . ($mismoDocente ? 'SÍ' : 'NO') . "\n";
echo "¿Misma aula? " . ($mismaAula ? 'SÍ' : 'NO') . "\n";

$bloques = $horarios->pluck('id_bloque')->unique();
echo "Bloques diferentes: " . $bloques->count() . " (" . $bloques->implode(', ') . ")\n";

if ($mismoGrupo && $mismoDocente && $mismaAula && $bloques->count() > 1) {
    echo "\n⚠️  PROBLEMA DETECTADO:\n";
    echo "   Estos horarios tienen el MISMO grupo, docente y aula,\n";
    echo "   pero están en BLOQUES DIFERENTES.\n";
    echo "   Esto significa que son clases en horarios distintos,\n";
    echo "   NO se pueden consolidar.\n";
} elseif ($mismoGrupo && $mismoDocente && $mismaAula && $bloques->count() === 1) {
    echo "\n✅ CONSOLIDABLES:\n";
    echo "   Estos horarios pueden consolidarse en uno solo\n";
    echo "   con días: [Lunes, Miércoles, Viernes]\n";
}
