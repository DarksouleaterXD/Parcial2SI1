<?php

/**
 * Script para verificar el estado de usuarios docentes
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "VERIFICACIÓN DE USUARIOS DOCENTES\n";
echo "========================================\n\n";

// Buscar todos los usuarios con rol 'docente'
$usuariosDocentes = DB::table('usuarios')
    ->where('rol', 'docente')
    ->get();

echo "Total de usuarios con rol 'docente': " . $usuariosDocentes->count() . "\n\n";

foreach ($usuariosDocentes as $usuario) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Usuario ID: {$usuario->id}\n";
    echo "Nombre: {$usuario->nombre}\n";
    echo "Email: {$usuario->email}\n";
    echo "ID Persona: " . ($usuario->id_persona ?? 'NULL') . "\n";

    // Verificar si tiene persona
    if ($usuario->id_persona) {
        $persona = DB::table('personas')->find($usuario->id_persona);
        if ($persona) {
            echo "✓ Persona encontrada:\n";
            echo "  - CI: {$persona->ci}\n";
            echo "  - Nombre: {$persona->nombre}\n";
            echo "  - Apellido Paterno: " . ($persona->apellido_paterno ?? 'NULL') . "\n";
            echo "  - Apellido Materno: " . ($persona->apellido_materno ?? 'NULL') . "\n";

            // Verificar si existe docente
            $docente = DB::table('docentes')
                ->where('id_persona', $persona->id)
                ->first();

            if ($docente) {
                echo "✓ Registro en tabla docentes: SÍ (ID: {$docente->id})\n";
            } else {
                echo "✗ Registro en tabla docentes: NO\n";
                echo "⚠️  PROBLEMA: Usuario es docente pero no tiene registro en tabla 'docentes'\n";
            }
        } else {
            echo "✗ ERROR: Persona no encontrada con ID {$usuario->id_persona}\n";
        }
    } else {
        echo "✗ ERROR: Usuario no tiene id_persona asociado\n";
        echo "⚠️  PROBLEMA: Usuario docente sin persona\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "VERIFICACIÓN DE TABLA DOCENTES\n";
echo "========================================\n\n";

$docentes = DB::table('docentes')
    ->join('personas', 'docentes.id_persona', '=', 'personas.id')
    ->leftJoin('usuarios', 'personas.id_usuario', '=', 'usuarios.id')
    ->select('docentes.*', 'personas.nombre', 'personas.ci', 'usuarios.email', 'usuarios.rol')
    ->get();

echo "Total de registros en tabla docentes: " . $docentes->count() . "\n\n";

foreach ($docentes as $docente) {
    echo "Docente ID: {$docente->id} | Persona: {$docente->nombre} | CI: {$docente->ci} | Email: " . ($docente->email ?? 'NULL') . " | Rol Usuario: " . ($docente->rol ?? 'NULL') . "\n";
}

echo "\n========================================\n";
echo "FIN DE VERIFICACIÓN\n";
echo "========================================\n";
