<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

// Simular autenticación
$user = User::find(12); // Joel Cuellar (docente)

echo "=== SIMULACIÓN MIDDLEWARE DOCENTE ===\n\n";
echo "Usuario: {$user->nombre}\n";
echo "Email: {$user->email}\n";
echo "ID: {$user->id}\n\n";

echo "VERIFICACIONES:\n";
echo "1. \$user->rol: '{$user->rol}'\n";
echo "2. \$user->rol === 'docente': " . ($user->rol === 'docente' ? 'TRUE' : 'FALSE') . "\n";
echo "3. \$user->tieneRol('Docente'): " . ($user->tieneRol('Docente') ? 'TRUE' : 'FALSE') . "\n";
echo "4. \$user->tieneRol('Super Administrador'): " . ($user->tieneRol('Super Administrador') ? 'TRUE' : 'FALSE') . "\n";
echo "5. \$user->tieneRol('Administrador'): " . ($user->tieneRol('Administrador') ? 'TRUE' : 'FALSE') . "\n\n";

echo "LÓGICA DEL MIDDLEWARE:\n";
$esAdmin = $user->rol === 'admin' || $user->tieneRol('Super Administrador') || $user->tieneRol('Administrador');
$esDocente = $user->rol === 'docente' || $user->tieneRol('Docente');

echo "esAdmin: " . ($esAdmin ? 'TRUE' : 'FALSE') . "\n";
echo "esDocente: " . ($esDocente ? 'TRUE' : 'FALSE') . "\n\n";

if ($esAdmin) {
    echo "✅ PASARÍA: Admin puede hacer todo\n";
} elseif ($esDocente) {
    echo "✅ PASARÍA: Es docente\n";
} else {
    echo "❌ FALLARÍA: No autorizado\n";
}

echo "\n\n=== SIMULACIÓN MIDDLEWARE COORDINADOR ===\n\n";
$user2 = User::find(11); // Maria Garcia (coordinador)

echo "Usuario: {$user2->nombre}\n";
echo "Email: {$user2->email}\n";
echo "ID: {$user2->id}\n\n";

echo "VERIFICACIONES:\n";
echo "1. \$user->rol: '{$user2->rol}'\n";
echo "2. \$user->rol === 'coordinador': " . ($user2->rol === 'coordinador' ? 'TRUE' : 'FALSE') . "\n";
echo "3. \$user->tieneRol('Coordinador'): " . ($user2->tieneRol('Coordinador') ? 'TRUE' : 'FALSE') . "\n";
echo "4. \$user->tieneRol('Super Administrador'): " . ($user2->tieneRol('Super Administrador') ? 'TRUE' : 'FALSE') . "\n";
echo "5. \$user->tieneRol('Administrador'): " . ($user2->tieneRol('Administrador') ? 'TRUE' : 'FALSE') . "\n\n";

echo "LÓGICA DEL MIDDLEWARE:\n";
$esAdmin2 = $user2->rol === 'admin' || $user2->tieneRol('Super Administrador') || $user2->tieneRol('Administrador');
$esCoordinador = $user2->rol === 'coordinador' || $user2->tieneRol('Coordinador');

echo "esAdmin: " . ($esAdmin2 ? 'TRUE' : 'FALSE') . "\n";
echo "esCoordinador: " . ($esCoordinador ? 'TRUE' : 'FALSE') . "\n\n";

if ($esAdmin2) {
    echo "✅ PASARÍA: Admin puede hacer todo\n";
} elseif ($esCoordinador) {
    echo "✅ PASARÍA: Es coordinador\n";
} else {
    echo "❌ FALLARÍA: No autorizado\n";
}
