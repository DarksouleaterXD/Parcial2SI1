<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Persona;
use Illuminate\Support\Facades\Hash;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║                CREACIÓN DE CUENTAS - SISTEMA FICCT                     ║\n";
echo "║                    Universidad Andina Simón Bolívar                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// Definición de usuarios a crear
$users = [
    // Superadmin Principal
    [
        'nombre' => 'Administrador',
        'apellido' => 'Sistema',
        'ci' => '0000000001',
        'correo' => 'admin@ficct.edu.ec',
        'user_email' => 'admin@ficct.edu.ec',
        'password' => 'admin123',
        'rol' => 'admin',
        'description' => 'Superadministrador del Sistema',
    ],
    // Coordinador Académico
    [
        'nombre' => 'Coordinador',
        'apellido' => 'Académico',
        'ci' => '1000000001',
        'correo' => 'coordinador@ficct.edu.ec',
        'user_email' => 'coordinador@ficct.edu.ec',
        'password' => 'coordinador123',
        'rol' => 'coordinador',
        'description' => 'Coordinador Académico',
    ],
    // Autoridad
    [
        'nombre' => 'Autoridad',
        'apellido' => 'Académica',
        'ci' => '1000000002',
        'correo' => 'autoridad@ficct.edu.ec',
        'user_email' => 'autoridad@ficct.edu.ec',
        'password' => 'autoridad123',
        'rol' => 'autoridad',
        'description' => 'Autoridad Académica',
    ],
    // Docentes
    [
        'nombre' => 'Juan Carlos',
        'apellido' => 'García López',
        'ci' => '1234567890',
        'correo' => 'juan.garcia@ficct.edu.ec',
        'user_email' => 'juan.garcia@ficct.edu.ec',
        'password' => 'docente123',
        'rol' => 'docente',
        'description' => 'Docente - Programación',
    ],
    [
        'nombre' => 'María',
        'apellido' => 'López Ramírez',
        'ci' => '0987654321',
        'correo' => 'maria.lopez@ficct.edu.ec',
        'user_email' => 'maria.lopez@ficct.edu.ec',
        'password' => 'docente123',
        'rol' => 'docente',
        'description' => 'Docente - Bases de Datos',
    ],
    [
        'nombre' => 'Carlos',
        'apellido' => 'Martínez Silva',
        'ci' => '5555555555',
        'correo' => 'carlos.martinez@ficct.edu.ec',
        'user_email' => 'carlos.martinez@ficct.edu.ec',
        'password' => 'docente123',
        'rol' => 'docente',
        'description' => 'Docente - Redes',
    ],
    [
        'nombre' => 'Pedro',
        'apellido' => 'Rodríguez Morales',
        'ci' => '2000000001',
        'correo' => 'pedro.rodriguez@ficct.edu.ec',
        'user_email' => 'pedro.rodriguez@ficct.edu.ec',
        'password' => 'docente123',
        'rol' => 'docente',
        'description' => 'Docente - Sistemas Operativos',
    ],
    [
        'nombre' => 'Ana',
        'apellido' => 'Fernández García',
        'ci' => '2000000002',
        'correo' => 'ana.fernandez@ficct.edu.ec',
        'user_email' => 'ana.fernandez@ficct.edu.ec',
        'password' => 'docente123',
        'rol' => 'docente',
        'description' => 'Docente - Ingeniería de Software',
    ],
    [
        'nombre' => 'Luis',
        'apellido' => 'Sánchez Pérez',
        'ci' => '2000000003',
        'correo' => 'luis.sanchez@ficct.edu.ec',
        'user_email' => 'luis.sanchez@ficct.edu.ec',
        'password' => 'docente123',
        'rol' => 'docente',
        'description' => 'Docente - Arquitectura de Software',
    ],
];

$createdCount = 0;
$updatedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($users as $userData) {
    try {
        // Verificar si el usuario ya existe
        $existingUser = User::where('email', $userData['user_email'])->first();

        if ($existingUser) {
            // Actualizar contraseña
            $existingUser->password = Hash::make($userData['password']);
            $existingUser->save();

            echo "🔄 ACTUALIZADO: {$userData['nombre']} {$userData['apellido']} ({$userData['user_email']})\n";
            echo "   └─ Contraseña actualizada\n\n";
            $updatedCount++;
            continue;
        }

        // Crear persona si no existe
        $persona = Persona::where('ci', $userData['ci'])->first();

        if (!$persona) {
            $persona = Persona::create([
                'nombre' => $userData['nombre'],
                'apellido' => $userData['apellido'],
                'correo' => $userData['correo'],
                'ci' => $userData['ci'],
            ]);
        }

        // Crear usuario
        $user = User::create([
            'nombre' => $userData['nombre'],
            'email' => $userData['user_email'],
            'password' => Hash::make($userData['password']),
            'rol' => $userData['rol'],
            'activo' => true,
            'id_persona' => $persona->id,
        ]);

        echo "✅ CREADO: {$userData['nombre']} {$userData['apellido']}\n";
        echo "   └─ Email: {$userData['user_email']}\n";
        echo "   └─ Rol: {$userData['rol']}\n";
        echo "   └─ CI: {$userData['ci']}\n\n";

        $createdCount++;

    } catch (\Exception $e) {
        echo "❌ ERROR: {$userData['nombre']} {$userData['apellido']}\n";
        echo "   └─ {$e->getMessage()}\n\n";
        $errorCount++;
    }
}

// Resumen
echo "\n╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           RESUMEN FINAL                               ║\n";
echo "╠════════════════════════════════════════════════════════════════════════╣\n";
echo "║ ✅ Usuarios creados:   " . str_pad($createdCount, 47) . "║\n";
echo "║ 🔄 Usuarios actualizados: " . str_pad($updatedCount, 43) . "║\n";
echo "║ ⏭️  Usuarios saltados:  " . str_pad($skippedCount, 47) . "║\n";
echo "║ ❌ Errores:            " . str_pad($errorCount, 47) . "║\n";
echo "║ 📊 Total en sistema:   " . str_pad(User::count(), 47) . "║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

?>
