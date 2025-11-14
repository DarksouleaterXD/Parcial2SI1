<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE USUARIO KEVIN ===\n\n";

// Buscar usuario Kevin
$user = App\Models\User::where('email', 'kevin@gmail.com')->first();

if (!$user) {
    echo "❌ Usuario kevin@gmail.com no encontrado\n";
    exit;
}

echo "✅ Usuario encontrado:\n";
echo "  ID: {$user->id}\n";
echo "  Email: {$user->email}\n";
echo "  Nombre: {$user->name}\n\n";

// Verificar relación con Persona
echo "=== RELACIÓN CON PERSONA ===\n";
$persona = $user->persona;

if (!$persona) {
    echo "❌ Usuario NO tiene persona asociada\n";
    echo "  Verificando campo id_persona en users...\n";

    $userRaw = \DB::table('usuarios')->where('id', $user->id)->first();
    echo "  Datos raw: " . json_encode($userRaw) . "\n";
    exit;
} else {
    echo "✅ Persona encontrada:\n";
    echo "  ID: {$persona->id}\n";
    echo "  Nombres: {$persona->nombres}\n";
    echo "  Apellidos: {$persona->apellido_paterno} {$persona->apellido_materno}\n\n";
}

// Verificar relación con Docente
echo "=== RELACIÓN CON DOCENTE ===\n";
$docente = App\Models\Docente::where('id_persona', $persona->id)->first();

if (!$docente) {
    echo "❌ Persona NO tiene perfil de docente\n";
    echo "  Verificando tabla docentes...\n";

    $docentesConEstaPersona = \DB::table('docentes')->where('id_persona', $persona->id)->get();
    echo "  Docentes con id_persona={$persona->id}: " . $docentesConEstaPersona->count() . "\n";

    if ($docentesConEstaPersona->count() > 0) {
        foreach ($docentesConEstaPersona as $d) {
            echo "    - Docente ID: {$d->id}, Activo: {$d->activo}\n";
        }
    }

    echo "\n  Todos los docentes:\n";
    $todosDocentes = \DB::table('docentes')->get();
    foreach ($todosDocentes as $d) {
        echo "    - ID: {$d->id}, id_persona: {$d->id_persona}, activo: {$d->activo}\n";
    }
} else {
    echo "✅ Docente encontrado:\n";
    echo "  ID: {$docente->id}\n";
    echo "  Activo: " . ($docente->activo ? 'Sí' : 'No') . "\n";
}

echo "\n=== VERIFICAR MODELO USER ===\n";
$userModel = new App\Models\User();
echo "Relación persona() existe: " . (method_exists($userModel, 'persona') ? 'Sí' : 'No') . "\n";

// Intentar obtener persona directamente
try {
    $personaTest = $user->persona;
    echo "user->persona funciona: Sí\n";
    echo "persona es null: " . ($personaTest === null ? 'Sí' : 'No') . "\n";
} catch (\Exception $e) {
    echo "user->persona genera error: " . $e->getMessage() . "\n";
}
