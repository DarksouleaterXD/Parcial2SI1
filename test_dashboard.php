<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Periodo;

echo "=== PROBANDO DASHBOARD COORDINADOR ===\n\n";

// Buscar un coordinador
$coordinador = User::whereHas('roles', function($q) {
    $q->where('nombre', 'Coordinador');
})->first();

if (!$coordinador) {
    echo "❌ No se encontró ningún coordinador\n";
    exit(1);
}

echo "✓ Coordinador encontrado: {$coordinador->nombre} (ID: {$coordinador->id})\n";

// Buscar período vigente
$periodo = Periodo::where('vigente', true)->first();

if (!$periodo) {
    echo "❌ No se encontró período vigente\n";
    exit(1);
}

echo "✓ Período vigente: {$periodo->nombre} (ID: {$periodo->id})\n\n";

// Simular el controlador
$controller = new App\Http\Controllers\DashboardController();

try {
    echo "--- PROBANDO ENDPOINT /dashboard/kpis ---\n";

    $request = new Illuminate\Http\Request();
    $request->merge([
        'periodo_id' => $periodo->id,
        'carrera_id' => null
    ]);

    $response = $controller->kpis($request);
    $kpis = json_decode($response->getContent(), true);

    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . $response->getContent() . "\n\n";

    if ($kpis['success']) {
        echo "✓ KPIs obtenidos exitosamente:\n";
        echo "  - Ocupación aulas: {$kpis['data']['ocupacion_aulas']['tasa_ocupacion']}%\n";
        echo "  - Asistencia promedio: {$kpis['data']['asistencia_promedio']['tasa_asistencia']}%\n";
        echo "  - Grupos activos: {$kpis['data']['grupos_activos']['grupos_activos']}\n";
        echo "  - Horas promedio docente: {$kpis['data']['horas_promedio_docente']['promedio']}\n";
    } else {
        echo "❌ Error en KPIs: " . ($kpis['message'] ?? 'Sin mensaje') . "\n";
        if (isset($kpis['error'])) {
            echo "   Error details: {$kpis['error']}\n";
        }
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "\n   Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

try {
    echo "--- PROBANDO ENDPOINT /dashboard/graficos ---\n";

    $request = new Illuminate\Http\Request();
    $request->merge([
        'periodo_id' => $periodo->id,
        'carrera_id' => null,
        'tipo' => 'todos'
    ]);

    $response = $controller->graficos($request);
    $graficos = json_decode($response->getContent(), true);

    echo "Response status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() !== 200) {
        echo "Response content: " . $response->getContent() . "\n\n";
    }

    if ($graficos['success']) {
        echo "✓ Gráficos obtenidos exitosamente:\n";

        if (isset($graficos['data']['ocupacion_por_dia'])) {
            echo "  - Ocupación por día: " . count($graficos['data']['ocupacion_por_dia']) . " días\n";
        }

        if (isset($graficos['data']['ocupacion_por_bloque'])) {
            echo "  - Ocupación por bloque: " . count($graficos['data']['ocupacion_por_bloque']) . " bloques\n";
        }

        if (isset($graficos['data']['asistencia_por_grupo'])) {
            echo "  - Asistencia por grupo: " . count($graficos['data']['asistencia_por_grupo']) . " grupos\n";
        }

        if (isset($graficos['data']['asistencia_por_semana'])) {
            echo "  - Asistencia por semana: " . count($graficos['data']['asistencia_por_semana']) . " semanas\n";
        }

        if (isset($graficos['data']['carga_por_docente'])) {
            echo "  - Carga por docente: " . count($graficos['data']['carga_por_docente']) . " docentes\n";
        }

        if (isset($graficos['data']['carga_por_carrera'])) {
            echo "  - Carga por carrera: " . count($graficos['data']['carga_por_carrera']) . " carreras\n";
        }
    } else {
        echo "❌ Error en gráficos: " . ($graficos['message'] ?? 'Sin mensaje') . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "\n   Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE PRUEBAS ===\n";
