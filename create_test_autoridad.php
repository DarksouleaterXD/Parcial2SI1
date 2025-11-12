<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Persona;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    // 1. Buscar el rol Autoridad
    $rolAutoridad = Rol::where('nombre', 'Autoridad')->first();

    if (!$rolAutoridad) {
        echo "❌ Error: No existe el rol 'Autoridad' en el sistema\n";
        echo "Ejecuta el RBACSeeder primero: php artisan db:seed --class=RBACSeeder\n";
        exit(1);
    }

    // 2. Verificar si ya existe el usuario
    $existingUser = User::where('email', 'autoridad@gestion.com')->first();
    if ($existingUser) {
        echo "⚠️  El usuario autoridad@gestion.com ya existe\n";
        echo "ID: {$existingUser->id}\n";
        echo "Nombre: {$existingUser->nombre}\n";
        echo "Rol: {$existingUser->rol}\n";

        // Actualizar rol RBAC si no lo tiene
        $tieneRol = DB::table('usuario_rol')
            ->where('id_usuario', $existingUser->id)
            ->where('id_rol', $rolAutoridad->id)
            ->exists();

        if (!$tieneRol) {
            DB::table('usuario_rol')->insert([
                'id_usuario' => $existingUser->id,
                'id_rol' => $rolAutoridad->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "✓ Rol RBAC 'Autoridad' asignado\n";
        } else {
            echo "✓ Ya tiene el rol RBAC 'Autoridad'\n";
        }

        DB::commit();
        exit(0);
    }

    // 3. Crear persona
    $persona = Persona::create([
        'ci' => '9999999',
        'nombre' => 'Autoridad',
        'apellido_paterno' => 'Académica',
        'apellido_materno' => 'Test',
        'correo' => 'autoridad@gestion.com',
        'telefono' => '77777777',
    ]);

    echo "✓ Persona creada (ID: {$persona->id})\n";

    // 4. Crear usuario
    $usuario = User::create([
        'nombre' => 'Autoridad Académica',
        'email' => 'autoridad@gestion.com',
        'password' => Hash::make('password'),
        'rol' => 'autoridad',
        'persona_id' => $persona->id,
    ]);

    echo "✓ Usuario creado (ID: {$usuario->id})\n";

    // 5. Asignar rol RBAC
    DB::table('usuario_rol')->insert([
        'id_usuario' => $usuario->id,
        'id_rol' => $rolAutoridad->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "✓ Rol RBAC asignado\n";

    DB::commit();

    echo "\n=== USUARIO AUTORIDAD CREADO ===\n";
    echo "Email: autoridad@gestion.com\n";
    echo "Password: password\n";
    echo "Rol Sistema: autoridad\n";
    echo "Rol RBAC: Autoridad\n";
    echo "\n✓ Puede usar este usuario para probar permisos de solo lectura\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
