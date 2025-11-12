<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN DE USUARIO ORLANDO ===\n\n";

// Buscar usuario
$user = DB::table('usuarios')->where('email', 'juan.perez@ejemplo.com')->first();

if (!$user) {
    echo "❌ Usuario juan.perez@ejemplo.com NO encontrado\n";
    echo "\nUsuarios con rol 'docente':\n";
    $docentes = DB::table('usuarios')->where('rol', 'docente')->get();
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
$docente = DB::table('docentes')->where('id_persona', $user->id_persona)->first();

if (!$docente) {
    echo "❌ NO TIENE REGISTRO DE DOCENTE VINCULADO\n";
    echo "\nNecesita crear registro en tabla 'docentes' con id_persona = {$user->id_persona}\n";
    
    echo "\nDocentes existentes:\n";
    $docentes = DB::table('docentes')
        ->join('personas', 'docentes.id_persona', '=', 'personas.id')
        ->select('docentes.*', 'personas.nombre')
        ->get();
    foreach ($docentes as $doc) {
        echo "- ID: {$doc->id}, Persona ID: {$doc->id_persona}, Nombre: {$doc->nombre}\n";
    }
} else {
    echo "✅ Registro de docente encontrado:\n";
    echo "   - ID Docente: {$docente->id}\n";
    echo "   - ID Persona: {$docente->id_persona}\n";
    
    $persona = DB::table('personas')->where('id', $docente->id_persona)->first();
    echo "   - Nombre: " . ($persona->nombre ?? 'N/A') . "\n";
    echo "   - Grado Académico: " . ($docente->grado_academico ?? 'N/A') . "\n";
    
    // Verificar si tiene horarios asignados
    $horarios = DB::table('horarios')->where('id_docente', $docente->id)->count();
    echo "   - Horarios asignados: {$horarios}\n";
    
    if ($horarios > 0) {
        echo "\n   Detalle de horarios:\n";
        $horariosDetalle = DB::table('horarios')
            ->join('grupos', 'horarios.id_grupo', '=', 'grupos.id')
            ->join('materias', 'grupos.id_materia', '=', 'materias.id')
            ->join('bloques_horarios', 'horarios.id_bloque', '=', 'bloques_horarios.id')
            ->where('horarios.id_docente', $docente->id)
            ->select('materias.nombre as materia', 'horarios.dia', 'bloques_horarios.numero_bloque', 'bloques_horarios.hora_inicio', 'bloques_horarios.hora_fin')
            ->get();
        
        foreach ($horariosDetalle as $h) {
            echo "   - {$h->materia} | {$h->dia} | Bloque {$h->numero_bloque} ({$h->hora_inicio} - {$h->hora_fin})\n";
        }
    }
}

echo "\n=== FIN VERIFICACIÓN ===\n";
