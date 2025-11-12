<?php

/**
 * Script para sincronizar usuarios docentes sin registro en tabla docentes
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Docente;
use App\Models\Persona;

echo "========================================\n";
echo "SINCRONIZACIÓN DE DOCENTES\n";
echo "========================================\n\n";

// Buscar todos los usuarios con rol 'docente'
$usuariosDocentes = DB::table('usuarios')
    ->where('rol', 'docente')
    ->get();

echo "Usuarios con rol 'docente': " . $usuariosDocentes->count() . "\n\n";

$sincronizados = 0;
$yaExistentes = 0;

foreach ($usuariosDocentes as $usuario) {
    echo "Procesando: {$usuario->nombre} (ID: {$usuario->id})...\n";

    // Verificar si tiene persona
    if (!$usuario->id_persona) {
        echo "  ⚠️  Usuario sin persona, creando...\n";

        // Crear persona
        $persona = Persona::create([
            'nombre' => explode(' ', $usuario->nombre)[0],
            'apellido_paterno' => isset(explode(' ', $usuario->nombre)[1]) ? explode(' ', $usuario->nombre)[1] : '',
            'apellido_materno' => isset(explode(' ', $usuario->nombre)[2]) ? explode(' ', $usuario->nombre)[2] : '',
            'ci' => 'TEMP-' . time() . '-' . $usuario->id,
            'correo' => $usuario->email,
            'id_usuario' => $usuario->id,
        ]);

        // Actualizar usuario con id_persona
        DB::table('usuarios')
            ->where('id', $usuario->id)
            ->update(['id_persona' => $persona->id]);

        $personaId = $persona->id;
        echo "  ✓ Persona creada (ID: {$persona->id})\n";
    } else {
        $personaId = $usuario->id_persona;
        echo "  ✓ Persona existente (ID: {$personaId})\n";
    }

    // Verificar si ya existe en tabla docentes
    $docenteExiste = Docente::where('id_persona', $personaId)->exists();

    if ($docenteExiste) {
        echo "  ℹ️  Ya existe en tabla docentes\n";
        $yaExistentes++;
    } else {
        // Crear registro en tabla docentes
        $docente = Docente::create([
            'id_persona' => $personaId,
            'activo' => $usuario->activo,
        ]);

        echo "  ✓ Creado en tabla docentes (ID: {$docente->id})\n";
        $sincronizados++;
    }

    echo "\n";
}

echo "========================================\n";
echo "RESUMEN\n";
echo "========================================\n";
echo "Total procesados: " . $usuariosDocentes->count() . "\n";
echo "Sincronizados: {$sincronizados}\n";
echo "Ya existentes: {$yaExistentes}\n";
echo "========================================\n";
