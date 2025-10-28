<?php
/**
 * Script de prueba para CU4 - Gestión de Aulas
 * Ejecutar: php test_aulas.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Route;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "           CU4 - PRUEBAS DE GESTIÓN DE AULAS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Obtener usuarios de prueba
$admin = User::where('email', 'admin@gestion.com')->first();
$coordinador = User::where('email', 'coordinador@gestion.com')->first();
$autoridad = User::where('email', 'autoridad@gestion.com')->first();
$docente = User::where('email', 'docente@gestion.com')->first();

if (!$admin) {
    echo "❌ No se encontró usuario admin\n";
    exit(1);
}

echo "✅ Usuarios de prueba encontrados:\n";
echo "   - Admin: {$admin->email}\n";
echo "   - Coordinador: {$coordinador?->email}\n";
echo "   - Autoridad: {$autoridad?->email}\n";
echo "   - Docente: {$docente?->email}\n\n";

echo "📝 ENDPOINTS DISPONIBLES:\n\n";

// Mostrar rutas disponibles
$routes = [
    'GET /api/aulas' => 'Listar aulas (con búsqueda y paginación)',
    'POST /api/aulas' => 'Crear nueva aula',
    'GET /api/aulas/{id}' => 'Obtener aula específica',
    'PUT /api/aulas/{id}' => 'Actualizar aula',
    'PATCH /api/aulas/{id}/estado' => 'Cambiar estado de aula',
    'DELETE /api/aulas/{id}' => 'Eliminar aula',
];

foreach ($routes as $endpoint => $description) {
    echo "   • $endpoint\n";
    echo "     └─ $description\n";
}

echo "\n📊 ACCESO POR ROL:\n\n";

$roleAccess = [
    'Admin' => ['✅ Crear', '✅ Editar', '✅ Ver', '✅ Eliminar', '✅ Cambiar estado'],
    'Coordinador' => ['✅ Crear', '✅ Editar', '✅ Ver', '✅ Eliminar', '✅ Cambiar estado'],
    'Autoridad' => ['❌ Acceso denegado (403)'],
    'Docente' => ['❌ Acceso denegado (403)'],
];

foreach ($roleAccess as $role => $permissions) {
    echo "   $role:\n";
    foreach ($permissions as $perm) {
        echo "      $perm\n";
    }
}

echo "\n🧪 DATOS DE PRUEBA EXISTENTES:\n\n";

$aulas = \App\Models\Aulas::all();
echo "   Total de aulas: " . count($aulas) . "\n";
foreach ($aulas as $aula) {
    $estado = $aula->activo ? '✅ Activa' : '❌ Inactiva';
    echo "   • [{$aula->codigo}] {$aula->nombre} ({$aula->tipo}) - Cap: {$aula->capacidad} $estado\n";
}

echo "\n📋 VALIDACIÓN DE CAMPOS:\n\n";

$validation = [
    'codigo' => 'Requerido, 1-20 caracteres, único',
    'nombre' => 'Requerido, 1-255 caracteres',
    'tipo' => 'Requerido, uno de: teorica, practica, laboratorio, mixta',
    'capacidad' => 'Requerido, entero entre 1-500',
    'ubicacion' => 'Opcional, máximo 255 caracteres',
    'piso' => 'Opcional, entero entre 0-20',
    'activo' => 'Requerido, 0 o 1 (boolean)',
];

foreach ($validation as $field => $rule) {
    echo "   • $field: $rule\n";
}

echo "\n💡 EJEMPLOS DE SOLICITUDES:\n\n";

echo "1️⃣  CREAR AULA:\n";
echo "   POST /api/aulas\n";
echo "   Content-Type: application/json\n";
echo "   Authorization: Bearer {token}\n\n";
echo "   {\n";
echo "     \"codigo\": \"A104\",\n";
echo "     \"nombre\": \"Aula 104 - Nueva\",\n";
echo "     \"tipo\": \"teorica\",\n";
echo "     \"capacidad\": 40,\n";
echo "     \"ubicacion\": \"Bloque A, Piso 1\",\n";
echo "     \"piso\": 1,\n";
echo "     \"activo\": 1\n";
echo "   }\n\n";

echo "2️⃣  BUSCAR AULAS:\n";
echo "   GET /api/aulas?search=A101&page=1&per_page=10\n\n";

echo "3️⃣  CAMBIAR ESTADO:\n";
echo "   PATCH /api/aulas/1/estado\n";
echo "   Content-Type: application/json\n";
echo "   Authorization: Bearer {token}\n\n";
echo "   {\n";
echo "     \"activo\": false\n";
echo "   }\n\n";

echo "4️⃣  ELIMINAR AULA:\n";
echo "   DELETE /api/aulas/1\n";
echo "   Authorization: Bearer {token}\n\n";

echo "📱 PRUEBAS DESDE FRONTEND:\n\n";

echo "   // Obtener lista\n";
echo "   const response = await fetch('/api/aulas?search=&page=1', {\n";
echo "     headers: { Authorization: 'Bearer ' + token }\n";
echo "   });\n\n";

echo "   // Crear\n";
echo "   const response = await fetch('/api/aulas', {\n";
echo "     method: 'POST',\n";
echo "     headers: {\n";
echo "       'Content-Type': 'application/json',\n";
echo "       'Authorization': 'Bearer ' + token\n";
echo "     },\n";
echo "     body: JSON.stringify({\n";
echo "       codigo: 'A105',\n";
echo "       nombre: 'Aula 105',\n";
echo "       tipo: 'practica',\n";
echo "       capacidad: 30,\n";
echo "       ubicacion: 'Bloque B',\n";
echo "       piso: 2,\n";
echo "       activo: 1\n";
echo "     })\n";
echo "   });\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ BACKEND LISTO PARA PRUEBAS\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\nPróximos pasos:\n";
echo "1. Crea la página en tu frontend: src/app/private/admin/aulas/page.tsx\n";
echo "2. Crea el componente de formulario: src/components/forms/AulaForm.tsx\n";
echo "3. Crea el componente modal: src/components/modals/AulaModal.tsx\n";
echo "4. Agrega el enlace en tu sidebar\n";
echo "5. Prueba con diferentes roles\n";
echo "\n";
