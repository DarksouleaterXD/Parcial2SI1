<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Obtener el docente Kevin (user_id = 2)
$docente = App\Models\Docente::whereHas('usuario', function($q) {
    $q->where('usuarios.id', 2);
})->first();

if (!$docente) {
    echo "No se encontró el docente\n";
    exit;
}

echo "Docente: {$docente->persona->nombre_completo}\n\n";

// Obtener las sesiones del docente para hoy
$hoy = now()->format('Y-m-d');
$diaSemana = now()->locale('es')->dayName;

echo "Fecha: {$hoy}\n";
echo "Día: {$diaSemana}\n\n";

$sesiones = App\Models\Sesion::whereHas('horario', function($q) use ($docente) {
    $q->where('docente_id', $docente->id);
})
->where('fecha', $hoy)
->with(['horario.materia', 'horario.grupo.carrera'])
->get();

echo "Sesiones encontradas: " . $sesiones->count() . "\n\n";

foreach ($sesiones as $sesion) {
    echo "ID: {$sesion->id}\n";
    echo "Horario ID: {$sesion->horario_id}\n";
    echo "Materia: {$sesion->horario->materia->nombre}\n";
    echo "Grupo: {$sesion->horario->grupo->nombre}\n";
    echo "Hora: {$sesion->hora_inicio} - {$sesion->hora_fin}\n";
    echo "Fecha: {$sesion->fecha}\n";
    echo "---\n";
}
