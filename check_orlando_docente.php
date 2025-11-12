<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICACIÓN DE USUARIO ORLANDO ===\n\n";

// Buscar usuario por email juan.perez@ejemplo.com (el docente de prueba)
$user = \App\Models\Usuario::where('email', 'juan.perez@ejemplo.com')->first();

if (!$user) {
    echo "❌ Usuario juan.perez@ejemplo.com NO encontrado\n";
    echo "\nBuscando usuarios con rol 'docente':\n";
    $docentes = \App\Models\Usuario::where('rol', 'docente')->get();
    foreach ($docentes as $doc) {
        echo "- Email: {$doc->email}, Persona ID: {$doc->id_persona}\n";
    }
    exit(1);
}

echo "✅ Usuario encontrado:\n";
echo "   - ID Usuario: {$user->id}\n";
echo "   - Email: {$user->email}\n";
echo "   - ID Persona: {$user->id_persona}\n";
echo "   - Rol: {$user->rol}\n\n";

// Buscar docente vinculado
$docente = \App\Models\Docente::where('id_persona', $user->id_persona)->first();

if (!$docente) {
    echo "❌ NO TIENE REGISTRO DE DOCENTE VINCULADO\n";
    echo "\nNecesita crear registro en tabla 'docentes' con id_persona = {$user->id_persona}\n";
    
    echo "\nDocentes existentes:\n";
    $docentes = \App\Models\Docente::with('persona')->get();
    foreach ($docentes as $doc) {
        echo "- ID: {$doc->id}, Persona ID: {$doc->id_persona}, Nombre: " . ($doc->persona->nombre ?? 'N/A') . "\n";
    }
} else {
    echo "✅ Registro de docente encontrado:\n";
    echo "   - ID Docente: {$docente->id}\n";
    echo "   - ID Persona: {$docente->id_persona}\n";
    echo "   - Nombre: " . ($docente->persona->nombre ?? 'N/A') . "\n";
    echo "   - Grado Académico: {$docente->grado_academico}\n";
    
    // Verificar si tiene horarios asignados
    $horarios = \App\Models\Horario::where('id_docente', $docente->id)->count();
    echo "   - Horarios asignados: {$horarios}\n";
}

echo "\n=== FIN VERIFICACIÓN ===\n";
