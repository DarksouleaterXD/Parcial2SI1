<?php

/**
 * Script para probar actualización de grupos sin turno
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Grupo;
use Illuminate\Support\Facades\Validator;

echo "=== PROBAR ACTUALIZACIÓN DE GRUPOS ===\n\n";

try {
    // Obtener el primer grupo
    $grupo = Grupo::first();

    if (!$grupo) {
        echo "❌ No hay grupos en la base de datos\n";
        exit(1);
    }

    echo "✅ Grupo encontrado: ID {$grupo->id}\n";
    echo "   Materia ID: {$grupo->id_materia}\n";
    echo "   Periodo ID: {$grupo->id_periodo}\n";
    echo "   Paralelo: {$grupo->paralelo}\n";
    echo "   Capacidad: {$grupo->capacidad}\n\n";

    // Datos de prueba para actualización (SIN turno)
    $testData = [
        'id_materia' => $grupo->id_materia,
        'id_periodo' => $grupo->id_periodo,
        'paralelo' => $grupo->paralelo,
        'capacidad' => 50,
    ];

    echo "📝 Probando actualización con datos:\n";
    echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

    // Validar datos (usando las mismas reglas del controlador)
    $validator = Validator::make($testData, [
        'id_materia' => ['sometimes', 'integer', 'exists:materias,id'],
        'id_periodo' => ['sometimes', 'integer', 'exists:periodos,id'],
        'paralelo' => ['sometimes', 'string', 'max:2', 'regex:/^[A-Z]{1,2}$/'],
        'capacidad' => ['sometimes', 'integer', 'min:1', 'max:500'],
        'codigo' => ['sometimes', 'string', 'max:10', 'unique:grupos,codigo,' . $grupo->id],
    ]);

    if ($validator->fails()) {
        echo "❌ Validación FALLÓ:\n";
        foreach ($validator->errors()->all() as $error) {
            echo "   - $error\n";
        }
        exit(1);
    }

    echo "✅ Validación PASÓ\n\n";

    // Actualizar el grupo
    $grupo->update($testData);

    echo "✅ Grupo actualizado exitosamente\n";
    echo "   Nueva capacidad: {$grupo->capacidad}\n\n";

    echo "🎉 ¡Todo funciona correctamente!\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
