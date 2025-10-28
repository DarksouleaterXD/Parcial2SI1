<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    // Listar todos los usuarios
    $users = User::all();

    echo "=== Usuarios en la Base de Datos ===\n";
    foreach ($users as $user) {
        echo "ID: {$user->id}, Email: {$user->email}, Nombre: {$user->nombre}, Rol: {$user->rol}\n";
    }

    // Crear un usuario admin si no existe
    $adminUser = User::where('email', 'admin@ficct.edu.ec')->first();

    if (!$adminUser) {
        echo "\nCreando usuario admin...\n";
        $adminUser = User::create([
            'nombre' => 'Admin FICCT',
            'email' => 'admin@ficct.edu.ec',
            'password' => Hash::make('admin123'),
            'rol' => 'admin',
            'activo' => true,
        ]);
        echo "✅ Usuario admin creado: {$adminUser->email}\n";
    } else {
        echo "\n✅ Usuario admin ya existe: {$adminUser->email}\n";
    }

    // Verificar periodos
    $periodos = \App\Models\Periodo::all();
    echo "\n=== Periodos en la Base de Datos ===\n";
    if ($periodos->count() > 0) {
        foreach ($periodos as $periodo) {
            echo "ID: {$periodo->id}, Nombre: {$periodo->nombre}, Activo: {$periodo->activo}\n";
        }
    } else {
        echo "Sin periodos registrados\n";
    }

    // Verificar grupos
    $grupos = \App\Models\Grupo::all();
    echo "\n=== Grupos en la Base de Datos ===\n";
    if ($grupos->count() > 0) {
        foreach ($grupos as $grupo) {
            echo "ID: {$grupo->id}, Materia: {$grupo->id_materia}, Periodo: {$grupo->id_periodo}, Paralelo: {$grupo->paralelo}\n";
        }
    } else {
        echo "Sin grupos registrados\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
