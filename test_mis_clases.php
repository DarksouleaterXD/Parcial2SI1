<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simular autenticación
$user = App\Models\User::find(16); // Kevin
auth()->login($user);

// Obtener el docente directamente por ID
$docente = App\Models\Docente::find(8); // Kevin es docente ID 8

if (!$docente) {
    echo "Docente no encontrado\n";
    exit;
}

echo "Docente autenticado: ID {$docente->id}\n\n";

// Simular el método misClasesHoy
$hoy = now()->format('Y-m-d');
$diaSemana = ucfirst(strtolower(now()->locale('es')->dayName));

echo "Hoy: {$hoy} ({$diaSemana})\n\n";

$sesiones = App\Models\Sesion::where('docente_id', $docente->id)
    ->where('fecha', $hoy)
    ->with(['horario.grupo.materia', 'aula'])
    ->orderBy('hora_inicio')
    ->get();

echo "Sesiones encontradas: " . $sesiones->count() . "\n\n";

foreach ($sesiones as $sesion) {
    echo "Sesión ID: {$sesion->id}\n";
    echo "Materia: {$sesion->horario->grupo->materia->nombre}\n";
    echo "Grupo: {$sesion->horario->grupo->nombre}\n";
    echo "Aula: {$sesion->aula->nombre}\n";
    echo "Hora: {$sesion->hora_inicio} - {$sesion->hora_fin}\n";
    echo "Ventana: {$sesion->ventana_inicio} - {$sesion->ventana_fin}\n";

    // Verificar si está dentro de ventana
    $dentroVentana = $sesion->dentroDeVentana();
    echo "Dentro de ventana: " . ($dentroVentana ? 'SÍ' : 'NO') . "\n";

    // Verificar si ya se registró asistencia
    $asistencia = App\Models\Asistencia::where('sesion_id', $sesion->id)
        ->where('docente_id', $docente->id)
        ->first();
    echo "Asistencia registrada: " . ($asistencia ? 'SÍ (ID: ' . $asistencia->id . ')' : 'NO') . "\n";
    echo "---\n";
}
