<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Imports\UsuariosImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Models\Persona;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== SIMULACIÓN DE CONFIRMACIÓN DE IMPORTACIÓN ===\n\n";

// Buscar el archivo de prueba
$archivo = storage_path('app/private/test_plantilla.xlsx');

if (!file_exists($archivo)) {
    die("❌ No se encuentra el archivo: $archivo\n");
}

echo "✅ Archivo encontrado: $archivo\n\n";

// Importar con validación (sin validar duplicados en BD)
echo "📋 Importando y validando archivo...\n";
$import = new UsuariosImport(true, false);
Excel::import($import, $archivo);

$resultados = $import->getResultados();
$estadisticas = $import->getEstadisticas();

echo "\n📊 Estadísticas:\n";
echo "   Total filas: {$estadisticas['total']}\n";
echo "   Válidas: {$estadisticas['validos']}\n";
echo "   Con errores: {$estadisticas['errores']}\n\n";

// Filtrar solo filas válidas
$filasValidas = array_filter($resultados, fn($r) => $r['valido']);

echo "✅ Filas válidas filtradas: " . count($filasValidas) . "\n\n";

if (empty($filasValidas)) {
    die("❌ No hay filas válidas para importar\n");
}

echo "🔄 Intentando crear usuarios...\n\n";

$usuariosCreados = [];
$erroresCreacion = [];

DB::beginTransaction();

try {
    foreach ($filasValidas as $fila) {
        echo "📝 Procesando fila {$fila['fila']}...\n";
        
        try {
            $datos = $fila['datos'];
            
            // Verificar datos
            echo "   CI: {$datos['ci']}\n";
            echo "   Email: {$datos['email']}\n";
            
            // Verificar si ya existe
            $personaExiste = Persona::where('ci', $datos['ci'])->exists();
            $usuarioExiste = User::where('email', $datos['email'])->exists();
            
            echo "   Persona existe: " . ($personaExiste ? 'SÍ' : 'NO') . "\n";
            echo "   Usuario existe: " . ($usuarioExiste ? 'SÍ' : 'NO') . "\n";
            
            if ($personaExiste || $usuarioExiste) {
                echo "   ⚠️ DUPLICADO - Saltando creación\n\n";
                $erroresCreacion[] = [
                    'fila' => $fila['fila'],
                    'error' => 'Usuario o persona ya existe en la base de datos',
                ];
                continue;
            }
            
            // Crear Persona
            $persona = Persona::create([
                'ci' => $datos['ci'],
                'correo' => $datos['email'], // Campo correo es requerido
                'nombre' => $datos['nombre'],
                'apellido_paterno' => $datos['apellido_paterno'],
                'apellido_materno' => $datos['apellido_materno'],
                'telefono' => $datos['telefono'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
            ]);
            
            echo "   ✅ Persona creada con ID: {$persona->id}\n";
            
            // Crear Usuario
            $usuario = User::create([
                'nombre' => $datos['nombre'] . ' ' . $datos['apellido_paterno'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password'] ?? 'temporal123'),
                'rol' => $datos['rol'],
                'id_persona' => $persona->id,
            ]);
            
            echo "   ✅ Usuario creado con ID: {$usuario->id}\n\n";
            
            $usuariosCreados[] = [
                'fila' => $fila['fila'],
                'usuario_id' => $usuario->id,
                'email' => $usuario->email,
            ];
            
        } catch (\Exception $e) {
            echo "   ❌ ERROR: {$e->getMessage()}\n\n";
            $erroresCreacion[] = [
                'fila' => $fila['fila'],
                'error' => $e->getMessage(),
            ];
        }
    }
    
    echo "\n🎯 Resultados finales:\n";
    echo "   Usuarios creados: " . count($usuariosCreados) . "\n";
    echo "   Errores: " . count($erroresCreacion) . "\n\n";
    
    if (!empty($erroresCreacion)) {
        echo "📋 Detalle de errores:\n";
        foreach ($erroresCreacion as $error) {
            echo "   Fila {$error['fila']}: {$error['error']}\n";
        }
        echo "\n";
    }
    
    echo "⚠️ Haciendo ROLLBACK (no se guardarán los cambios)\n";
    DB::rollBack();
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ Error general: {$e->getMessage()}\n";
}

echo "\n✅ Simulación completada\n";
