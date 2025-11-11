<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Imports\UsuariosImport;
use Maatwebsite\Excel\Facades\Excel;

echo "=== PRUEBA DE IMPORTACIÓN ===\n\n";

// Usar el archivo de prueba generado
$archivo = __DIR__ . '\\storage\\app\\private\\test_plantilla.xlsx';

if (!file_exists($archivo)) {
    echo "❌ Archivo no existe: $archivo\n";
    exit(1);
}

echo "📄 Procesando: $archivo\n\n";

// Simular importación con validación de BD desactivada
$import = new UsuariosImport(true, false);
Excel::import($import, $archivo);

$resultados = $import->getResultados();
$estadisticas = $import->getEstadisticas();

echo "📊 ESTADÍSTICAS:\n";
echo "  Total filas: {$estadisticas['total']}\n";
echo "  Válidas: {$estadisticas['validos']}\n";
echo "  Con errores: {$estadisticas['errores']}\n\n";

echo "📋 RESULTADOS DETALLADOS:\n";
echo str_repeat("-", 80) . "\n";

foreach ($resultados as $resultado) {
    $status = $resultado['valido'] ? '✅' : '❌';
    echo "{$status} Fila {$resultado['fila']}: ";
    echo $resultado['datos']['nombre'] ?? 'SIN NOMBRE';

    if (!$resultado['valido']) {
        echo " - ERRORES: " . implode(', ', $resultado['errores']);
    }
    echo "\n";
}

echo str_repeat("-", 80) . "\n";

// Contar válidas
$filasValidas = array_filter($resultados, fn($r) => $r['valido']);
echo "\n✅ Total filas válidas para importar: " . count($filasValidas) . "\n";

if (empty($filasValidas)) {
    echo "\n❌ NO HAY FILAS VÁLIDAS - Los usuarios NO se crearían\n";
} else {
    echo "\n✅ HAY FILAS VÁLIDAS - Los usuarios SÍ se crearían\n";
}
