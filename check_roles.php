<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ROLES EN EL SISTEMA ===\n\n";

$roles = DB::table('roles')->get();
foreach($roles as $rol) {
    echo "ID: {$rol->id} - Nombre: {$rol->nombre}\n";
}

echo "\n=== USUARIOS CON ROL COORDINADOR ===\n\n";

$usuarios = DB::table('usuarios')
    ->join('usuario_rol', 'usuarios.id', '=', 'usuario_rol.id_usuario')
    ->join('roles', 'usuario_rol.id_rol', '=', 'roles.id')
    ->where('roles.nombre', 'Coordinador')
    ->select('usuarios.id', 'usuarios.nombre', 'usuarios.email', 'roles.nombre as rol')
    ->get();

if ($usuarios->isEmpty()) {
    echo "No hay coordinadores en el sistema\n";
} else {
    foreach($usuarios as $u) {
        echo "ID: {$u->id} - Nombre: {$u->nombre} - Email: {$u->email} - Rol: {$u->rol}\n";
    }
}
