<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Aulas;

try {
    echo "Creando aulas de prueba...\n\n";

    $aulasData = [
        ['codigo' => 'A101', 'nombre' => 'Aula 101 Teoría', 'tipo' => 'teorica', 'capacidad' => 40, 'ubicacion' => 'Bloque A, Piso 1', 'piso' => 1],
        ['codigo' => 'A102', 'nombre' => 'Aula 102 Teoría', 'tipo' => 'teorica', 'capacidad' => 35, 'ubicacion' => 'Bloque A, Piso 1', 'piso' => 1],
        ['codigo' => 'LAB101', 'nombre' => 'Laboratorio Informática', 'tipo' => 'laboratorio', 'capacidad' => 25, 'ubicacion' => 'Bloque B, Piso 2', 'piso' => 2],
        ['codigo' => 'LAB102', 'nombre' => 'Laboratorio Redes', 'tipo' => 'laboratorio', 'capacidad' => 20, 'ubicacion' => 'Bloque B, Piso 2', 'piso' => 2],
        ['codigo' => 'P101', 'nombre' => 'Aula Práctica 1', 'tipo' => 'practica', 'capacidad' => 30, 'ubicacion' => 'Bloque C, Piso 1', 'piso' => 1],
        ['codigo' => 'MIXTA01', 'nombre' => 'Aula Mixta 1', 'tipo' => 'mixta', 'capacidad' => 45, 'ubicacion' => 'Bloque D, Piso 3', 'piso' => 3],
    ];

    foreach ($aulasData as $data) {
        $aula = Aulas::create(array_merge($data, ['activo' => true]));
        echo "✅ Aula creada: {$aula->codigo} - {$aula->nombre} (Capacidad: {$aula->capacidad})\n";
    }

    echo "\n════════════════════════════════════════\n";
    echo "✅ Aulas de prueba creadas exitosamente\n";
    echo "════════════════════════════════════════\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
