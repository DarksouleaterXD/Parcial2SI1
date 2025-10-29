<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Persona;
use Illuminate\Support\Facades\Hash;

try {
    // Crear persona para el admin
    $persona = Persona::create([
        'nombre' => 'Admin',
        'apellido' => 'Sistema',
        'correo' => 'admin@gestion.com',
        'ci' => '0000000000',
    ]);

    echo "✅ Persona creada: ID " . $persona->id . "\n";

    // Crear usuario admin
    $admin = User::create([
        'nombre' => 'Admin',
        'email' => 'admin@gestion.com',
        'password' => Hash::make('admin12345'),
        'rol' => 'admin',
        'activo' => true,
        'id_persona' => $persona->id,
    ]);

    echo "✅ Superadmin creado exitosamente\n";
    echo "════════════════════════════════════════\n";
    echo "Email: " . $admin->email . "\n";
    echo "Password: admin12345\n";
    echo "Rol: " . $admin->rol . "\n";
    echo "ID: " . $admin->id . "\n";
    echo "════════════════════════════════════════\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
