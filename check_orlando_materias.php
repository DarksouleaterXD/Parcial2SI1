<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Docente;
use App\Models\Horario;

echo "=== VERIFICACIÓN DE HORARIOS DE ORLANDO ===\n\n";

$user = User::where('email', 'juan.perez@ejemplo.com')->first();
if (!$user) {
    echo "❌ Usuario no encontrado\n";
    exit;
}

$docente = Docente::where('id_persona', $user->id_persona)->first();
if (!$docente) {
    echo "❌ Docente no encontrado\n";
    exit;
}

echo "Docente ID: {$docente->id}\n";
echo "Nombre: {$docente->persona->nombre}\n\n";

// Obtener horarios incluyendo soft-deleted
$horarios = Horario::withTrashed()
    ->with(['grupo.materia', 'bloque', 'aula'])
    ->where('id_docente', $docente->id)
    ->get();

echo "Total horarios (incluyendo eliminados): {$horarios->count()}\n\n";

foreach ($horarios as $h) {
    $deleted = $h->trashed() ? '🗑️ ELIMINADO' : '✅ ACTIVO';
    $materia = $h->grupo->materia->nombre ?? 'N/A';
    $grupo = $h->grupo->codigo ?? 'N/A';
    $dias = implode(', ', $h->dias_semana ?? []);
    $bloque = $h->bloque->nombre ?? 'N/A';
    $horario = ($h->bloque->hora_inicio ?? '') . ' - ' . ($h->bloque->hora_fin ?? '');
    
    echo "- ID: {$h->id} {$deleted}\n";
    echo "  Materia: {$materia}\n";
    echo "  Grupo: {$grupo}\n";
    echo "  Días: {$dias}\n";
    echo "  Bloque: {$bloque} ({$horario})\n";
    echo "\n";
}

echo "=== FIN VERIFICACIÓN ===\n";
