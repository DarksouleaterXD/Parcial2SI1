<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Exports\PlantillaUsuariosExport;
use Maatwebsite\Excel\Facades\Excel;

echo "Probando generación de plantilla...\n\n";

try {
    $export = new PlantillaUsuariosExport();

    echo "Clase PlantillaUsuariosExport creada correctamente\n";

    // Probar el método array()
    $data = $export->array();
    echo "Datos del array: " . count($data) . " filas\n";
    print_r($data);

    // Probar el método headings()
    $headings = $export->headings();
    echo "\nHeaders: " . count($headings) . " columnas\n";
    print_r($headings);

    // Intentar generar el archivo
    echo "\n\nGenerando archivo Excel...\n";
    $filename = 'test_plantilla.xlsx';
    Excel::store($export, $filename, 'local');
    echo "✓ Archivo generado exitosamente: storage/app/$filename\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
