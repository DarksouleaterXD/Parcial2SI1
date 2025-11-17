<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Periodo;

echo "=== Verificando acceso de coordinador ===" . PHP_EOL . PHP_EOL;

// Buscar usuarios con rol coordinador
$coordinadores = User::where('rol', 'coordinador')->get();

echo "📊 Total coordinadores: " . $coordinadores->count() . PHP_EOL . PHP_EOL;

foreach ($coordinadores as $coord) {
    echo "👤 Coordinador:" . PHP_EOL;
    echo "   ID: " . $coord->id . PHP_EOL;
    echo "   Nombre: " . $coord->nombre . PHP_EOL;
    echo "   Email: " . $coord->email . PHP_EOL;
    echo "   Activo: " . ($coord->activo ? 'Sí' : 'No') . PHP_EOL;
    echo PHP_EOL;
}

// Verificar períodos disponibles
echo "📅 Períodos disponibles:" . PHP_EOL . PHP_EOL;
$periodos = Periodo::all();

foreach ($periodos as $periodo) {
    echo "  • " . $periodo->nombre . " (ID: " . $periodo->id . ")" . PHP_EOL;
    echo "    Vigente: " . ($periodo->vigente ? 'Sí' : 'No') . PHP_EOL;
    echo "    Fecha inicio: " . $periodo->fecha_inicio . PHP_EOL;
    echo "    Fecha fin: " . $periodo->fecha_fin . PHP_EOL;
    echo PHP_EOL;
}

// Si hay coordinador, simular las consultas que hace el dashboard
if ($coordinadores->count() > 0) {
    $periodo_vigente = Periodo::where('vigente', true)->first();

    if ($periodo_vigente) {
        echo "📈 Simulando consultas del dashboard con período vigente (ID: " . $periodo_vigente->id . "):" . PHP_EOL . PHP_EOL;

        // Ocupación de aulas
        $total_aulas = \App\Models\Aulas::where('activo', true)->count();
        echo "  Total aulas activas: " . $total_aulas . PHP_EOL;

        // Horarios del período
        $horarios = \App\Models\Horario::where('periodo_id', $periodo_vigente->id)
            ->where('activo', true)
            ->count();
        echo "  Horarios del período: " . $horarios . PHP_EOL;

        // Grupos activos
        $grupos = \App\Models\Grupo::where('periodo_id', $periodo_vigente->id)
            ->where('activo', true)
            ->count();
        echo "  Grupos activos: " . $grupos . PHP_EOL;

        // Asistencias
        $asistencias = \App\Models\Asistencia::count();
        echo "  Total asistencias registradas: " . $asistencias . PHP_EOL;

    } else {
        echo "⚠️  No hay período vigente configurado" . PHP_EOL;
    }
}
