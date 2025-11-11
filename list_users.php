<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;

$users = User::all();

echo "\n=== USUARIOS EN EL SISTEMA ===\n\n";
echo str_pad("ID", 5) . str_pad("Nombre", 30) . str_pad("Email", 35) . str_pad("Rol Sistema", 20) . "Roles RBAC\n";
echo str_repeat("-", 120) . "\n";

foreach ($users as $user) {
    // Obtener roles RBAC del usuario
    $rolesRbac = DB::table('usuario_rol')
        ->join('roles', 'usuario_rol.id_rol', '=', 'roles.id')
        ->where('usuario_rol.id_usuario', $user->id)
        ->pluck('roles.nombre')
        ->implode(', ');

    echo str_pad($user->id, 5) .
         str_pad($user->nombre, 30) .
         str_pad($user->email, 35) .
         str_pad($user->rol ?? 'N/A', 20) .
         ($rolesRbac ?: 'Sin roles RBAC') . "\n";
}

echo "\nTotal de usuarios: " . $users->count() . "\n\n";
