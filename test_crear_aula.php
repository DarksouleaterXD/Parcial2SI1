<?php

/**
 * Test para crear un aula con el nuevo sistema
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Aulas;
use App\Models\User;

echo "=== TEST: CREAR AULA CON NUMERO_AULA ===\n\n";

// 1. Login como admin
$admin = User::where('rol', 'admin')->first();
if (!$admin) {
    echo "❌ No se encontró usuario admin\n";
    exit(1);
}

auth()->login($admin);
echo "✓ Autenticado como: {$admin->email}\n\n";

// 2. Crear un aula de prueba
$numeroAula = 101;

echo "Creando aula con número: $numeroAula\n";

$aula = Aulas::create([
    'codigo' => 'AULA_' . $numeroAula,
    'nombre' => 'Aula de Clases ' . $numeroAula,
    'tipo' => 'teorica',
    'capacidad' => 40,
    'ubicacion' => 'Edificio Principal',
    'numero_aula' => $numeroAula,
    'piso' => 1,
    'activo' => true,
]);

echo "\n✓ Aula creada:\n";
echo "  ID: {$aula->id}\n";
echo "  Código: {$aula->codigo}\n";
echo "  Nombre: {$aula->nombre}\n";
echo "  Número de Aula: {$aula->numero_aula}\n";
echo "  Piso: {$aula->piso}\n";
echo "  Capacidad: {$aula->capacidad}\n";
echo "  Ubicación: {$aula->ubicacion}\n";

// 3. Verificar que se guardó correctamente
$aulaDB = Aulas::find($aula->id);
if ($aulaDB && $aulaDB->numero_aula == $numeroAula) {
    echo "\n✓ Aula guardada correctamente en la base de datos\n";
} else {
    echo "\n❌ Error al guardar aula\n";
    exit(1);
}

// 4. Limpiar - Eliminar aula de prueba
$aula->delete();
echo "\n✓ Aula de prueba eliminada\n";

echo "\n=== TEST EXITOSO ===\n";
