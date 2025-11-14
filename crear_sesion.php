<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$hoy = now()->format('Y-m-d');
$horarioId = 25; // Estructuras Discretas - Viernes 11:30-13:00

// Verificar si ya existe la sesión
$sesionExistente = App\Models\Sesion::where('horario_id', $horarioId)
    ->where('fecha', $hoy)
    ->first();

if ($sesionExistente) {
    echo "Ya existe una sesión para hoy:\n";
    echo "ID: {$sesionExistente->id}\n";
    echo "Fecha: {$sesionExistente->fecha}\n";
    echo "Hora: {$sesionExistente->hora_inicio} - {$sesionExistente->hora_fin}\n";
} else {
    // Crear la sesión
    $horario = App\Models\Horario::with('bloque')->find($horarioId);

    $sesion = App\Models\Sesion::create([
        'horario_id' => $horarioId,
        'docente_id' => $horario->id_docente,
        'aula_id' => $horario->id_aula,
        'grupo_id' => $horario->id_grupo,
        'fecha' => $hoy,
        'hora_inicio' => $horario->bloque->hora_inicio,
        'hora_fin' => $horario->bloque->hora_fin,
        'estado' => 'programada',
    ]);

    // Calcular ventana
    $sesion->calcularVentanaMarcado();
    $sesion->save();

    echo "Sesión creada:\n";
    echo "ID: {$sesion->id}\n";
    echo "Horario ID: {$sesion->horario_id}\n";
    echo "Docente ID: {$sesion->docente_id}\n";
    echo "Aula ID: {$sesion->aula_id}\n";
    echo "Grupo ID: {$sesion->grupo_id}\n";
    echo "Fecha: {$sesion->fecha}\n";
    echo "Hora: {$sesion->hora_inicio} - {$sesion->hora_fin}\n";
    echo "Estado: {$sesion->estado}\n";
    echo "Ventana: {$sesion->ventana_inicio} - {$sesion->ventana_fin}\n";
}
