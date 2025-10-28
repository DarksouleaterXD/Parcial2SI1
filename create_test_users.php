<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Persona;
use Illuminate\Support\Facades\Hash;

try {
    echo "Creando usuarios de prueba para cada rol...\n\n";

    // Coordinador
    $persona_coord = Persona::create([
        'nombre' => 'María',
        'apellido' => 'García',
        'ci' => '12345678',
        'correo' => 'maria@gestion.com',
    ]);

    $coordinador = User::create([
        'nombre' => 'María García',
        'email' => 'coordinador@gestion.com',
        'password' => Hash::make('Coordinador123'),
        'rol' => 'coordinador',
        'id_persona' => $persona_coord->id,
        'activo' => true
    ]);

    echo "✅ Coordinador creado\n";
    echo "   Email: coordinador@gestion.com\n";
    echo "   Contraseña: Coordinador123\n\n";

    // Autoridad
    $persona_aut = Persona::create([
        'nombre' => 'Juan',
        'apellido' => 'Pérez',
        'ci' => '87654321',
        'correo' => 'juan@gestion.com',
    ]);

    $autoridad = User::create([
        'nombre' => 'Juan Pérez',
        'email' => 'autoridad@gestion.com',
        'password' => Hash::make('Autoridad123'),
        'rol' => 'autoridad',
        'id_persona' => $persona_aut->id,
        'activo' => true
    ]);

    echo "✅ Autoridad creada\n";
    echo "   Email: autoridad@gestion.com\n";
    echo "   Contraseña: Autoridad123\n\n";

    // Docente
    $persona_doc = Persona::create([
        'nombre' => 'Carlos',
        'apellido' => 'López',
        'ci' => '11223344',
        'correo' => 'carlos@gestion.com',
    ]);

    $docente = User::create([
        'nombre' => 'Carlos López',
        'email' => 'docente@gestion.com',
        'password' => Hash::make('Docente123'),
        'rol' => 'docente',
        'id_persona' => $persona_doc->id,
        'activo' => true
    ]);

    echo "✅ Docente creado\n";
    echo "   Email: docente@gestion.com\n";
    echo "   Contraseña: Docente123\n\n";

    echo "════════════════════════════════════════\n";
    echo "✅ Usuarios de prueba creados exitosamente\n";
    echo "════════════════════════════════════════\n\n";

    echo "RESUMEN DE USUARIOS:\n";
    echo "1. Admin (Superusuario) - admin@gestion.com / Admin123456\n";
    echo "2. Coordinador - coordinador@gestion.com / Coordinador123\n";
    echo "3. Autoridad - autoridad@gestion.com / Autoridad123\n";
    echo "4. Docente - docente@gestion.com / Docente123\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
