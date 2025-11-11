<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Exports\PlantillaUsuariosExport;
use Maatwebsite\Excel\Facades\Excel;

echo "=== GENERANDO PLANTILLA CON DATOS NUEVOS PARA PROBAR ===\n\n";

// Datos de ejemplo con CIs NUEVOS que no existen
$datosEjemplo = [
    [
        'ci' => 99887766,
        'nombre' => 'María',
        'apellido_paterno' => 'García',
        'apellido_materno' => 'Rojas',
        'email' => 'maria.garcia@ejemplo.com',
        'telefono' => 71234567,
        'rol' => 'docente',
        'fecha_nacimiento' => '1992-08-20',
        'password' => 'Maria2024!'
    ],
    [
        'ci' => 88776655,
        'nombre' => 'Carlos',
        'apellido_paterno' => 'Mendoza',
        'apellido_materno' => 'Silva',
        'email' => 'carlos.mendoza@ejemplo.com',
        'telefono' => 72345678,
        'rol' => 'coordinador',
        'fecha_nacimiento' => '1988-03-15',
        'password' => 'Carlos123'
    ],
    [
        'ci' => 77665544,
        'nombre' => 'Ana',
        'apellido_paterno' => 'Torres',
        'apellido_materno' => 'Vega',
        'email' => 'ana.torres@ejemplo.com',
        'telefono' => 73456789,
        'rol' => 'docente',
        'fecha_nacimiento' => '1995-11-10',
        'password' => 'AnaTorres2024'
    ],
];

// Crear export personalizado
$export = new PlantillaUsuariosExport();

// Guardar en storage/app/private
$rutaArchivo = 'private/plantilla_usuarios_nuevos.xlsx';
Excel::store($export, $rutaArchivo);

echo "✅ Plantilla generada exitosamente!\n";
echo "📂 Ubicación: storage/app/{$rutaArchivo}\n";
echo "📊 Contiene datos de ejemplo con 3 usuarios nuevos:\n";
echo "   - María García (CI: 99887766)\n";
echo "   - Carlos Mendoza (CI: 88776655)\n";
echo "   - Ana Torres (CI: 77665544)\n\n";

echo "📝 Para usarla:\n";
echo "1. Descárgala desde el frontend (botón 'Descargar Plantilla')\n";
echo "2. O cópiala desde: " . storage_path("app/{$rutaArchivo}") . "\n";
echo "3. Los datos de ejemplo ya tienen CIs únicos que no existen en la BD\n";
echo "4. Puedes editarla o usar directamente los datos de ejemplo\n\n";

// También actualizar el archivo de prueba
Excel::store($export, 'private/test_plantilla.xlsx');
echo "✅ También se actualizó test_plantilla.xlsx con los nuevos datos\n";
