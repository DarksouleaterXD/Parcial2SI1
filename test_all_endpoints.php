<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$endpoints = [
    'carreras',
    'materias',
    'grupos',
    'aulas',
    'horarios',
    'asistencias',
    'personas',
    'docentes',
    'usuarios',
    'bitacoras',
    'gestion-academica'
];

echo "═══════════════════════════════════════════════════════════════\n";
echo "           🧪 PRUEBAS DE ENDPOINTS API\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$passed = 0;
$failed = 0;

foreach ($endpoints as $endpoint) {
    try {
        // Prueba GET
        $request = \Illuminate\Http\Request::create("/api/$endpoint", 'GET');
        $response = $kernel->handle($request);
        $status = $response->status();

        if ($status === 200) {
            echo "✅ GET /api/$endpoint .................. Status: $status\n";
            $passed++;
        } else {
            echo "❌ GET /api/$endpoint .................. Status: $status\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "❌ GET /api/$endpoint .................. Error: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "📊 RESUMEN:\n";
echo "   ✅ Exitosos: $passed\n";
echo "   ❌ Fallos: $failed\n";
echo "   📈 Total: " . ($passed + $failed) . "\n";
echo "═══════════════════════════════════════════════════════════════\n";

if ($failed === 0) {
    echo "\n🎉 ¡TODOS LOS ENDPOINTS FUNCIONAN CORRECTAMENTE!\n";
}
