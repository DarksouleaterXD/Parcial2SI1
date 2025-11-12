<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Materia;

echo "=== TODOS LOS HORARIOS ACTIVOS ===\n\n";

$horarios = Horario::with(['grupo.materia', 'bloque', 'aula', 'docente.persona'])
    ->get();

echo "Total horarios: {$horarios->count()}\n\n";

foreach ($horarios as $h) {
    $materia = $h->grupo->materia->nombre ?? 'N/A';
    $grupo = $h->grupo->codigo ?? 'N/A';
    $dias = implode(', ', $h->dias_semana ?? []);
    $bloque = $h->bloque->nombre ?? 'N/A';
    $horario = ($h->bloque->hora_inicio ?? '') . ' - ' . ($h->bloque->hora_fin ?? '');
    $docente = ($h->docente->persona->nombre ?? 'N/A') . ' ' . ($h->docente->persona->apellido_paterno ?? '');
    $docenteId = $h->docente->id ?? 'N/A';
    
    echo "- ID: {$h->id}\n";
    echo "  Materia: {$materia}\n";
    echo "  Grupo: {$grupo}\n";
    echo "  Días: {$dias}\n";
    echo "  Bloque: {$bloque}\n";
    echo "  Horario: {$horario}\n";
    echo "  Docente: {$docente} (ID: {$docenteId})\n";
    echo "\n";
}

echo "\n=== MATERIAS DISPONIBLES ===\n\n";
$materias = Materia::all();
foreach ($materias as $m) {
    echo "- ID: {$m->id} | {$m->codigo} | {$m->nombre}\n";
}

echo "\n=== FIN VERIFICACIÓN ===\n";
