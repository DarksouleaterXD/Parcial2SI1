<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== VERIFICAR Y LIMPIAR TABLAS ===\n\n";

try {
    // Verificar tabla asistencias
    if (Schema::hasTable('asistencias')) {
        echo "✅ Tabla 'asistencias' existe\n";
        echo "   Eliminando tabla...\n";
        Schema::dropIfExists('asistencias');
        echo "   ✅ Tabla eliminada\n\n";
    } else {
        echo "❌ Tabla 'asistencias' NO existe\n\n";
    }

    // Verificar tabla sesiones
    if (Schema::hasTable('sesiones')) {
        echo "✅ Tabla 'sesiones' existe\n";
        echo "   Eliminando tabla...\n";
        Schema::dropIfExists('sesiones');
        echo "   ✅ Tabla eliminada\n\n";
    } else {
        echo "❌ Tabla 'sesiones' NO existe\n\n";
    }

    echo "🎉 Listo. Ahora puedes ejecutar: php artisan migrate\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
