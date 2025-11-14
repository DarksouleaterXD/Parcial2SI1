<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== USUARIOS ===\n";
$usuarios = App\Models\User::with('persona')->get();
foreach ($usuarios as $user) {
    echo "ID: {$user->id} | Email: {$user->email} | Persona: " . ($user->persona ? $user->persona->nombre_completo : 'N/A') . "\n";
}

echo "\n=== DOCENTES ===\n";
$docentes = App\Models\Docente::with('persona.usuario')->get();
foreach ($docentes as $docente) {
    $usuario = $docente->usuario ?? null;
    echo "ID: {$docente->id} | Persona: {$docente->persona->nombre_completo} | Usuario ID: " . ($usuario ? $usuario->id : 'N/A') . "\n";
}
