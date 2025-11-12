<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CREANDO HORARIOS PARA ORLANDO ===\n\n";

try {
    DB::beginTransaction();
    
    // Verificar docente
    $docente = DB::table('docentes')->where('id_persona', 17)->first();
    if (!$docente) {
        echo "❌ Docente no encontrado\n";
        exit(1);
    }
    
    echo "✅ Docente encontrado: ID {$docente->id}\n\n";
    
    // Obtener periodo vigente
    $periodo = DB::table('periodos')->where('vigente', true)->first();
    if (!$periodo) {
        $periodo = DB::table('periodos')->orderBy('id', 'desc')->first();
    }
    
    if (!$periodo) {
        echo "❌ No hay periodos disponibles\n";
        exit(1);
    }
    
    echo "✅ Periodo: {$periodo->nombre} (ID: {$periodo->id})\n\n";
    
    // Obtener una materia
    $materia = DB::table('materias')->where('activo', true)->first();
    if (!$materia) {
        echo "❌ No hay materias disponibles\n";
        exit(1);
    }
    
    echo "✅ Materia: {$materia->nombre} (ID: {$materia->id})\n\n";
    
    // Obtener un aula
    $aula = DB::table('aulas')->where('activo', true)->first();
    if (!$aula) {
        echo "❌ No hay aulas disponibles\n";
        exit(1);
    }
    
    echo "✅ Aula: {$aula->nombre} (ID: {$aula->id})\n\n";
    
    // Crear grupo si no existe
    $grupo = DB::table('grupos')
        ->where('id_materia', $materia->id)
        ->where('id_periodo', $periodo->id)
        ->where('id_docente', $docente->id)
        ->first();
    
    if (!$grupo) {
        $grupoId = DB::table('grupos')->insertGetId([
            'id_materia' => $materia->id,
            'id_periodo' => $periodo->id,
            'id_docente' => $docente->id,
            'paralelo' => 'A',
            'capacidad' => 30,
            'codigo' => $materia->codigo . '-A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "✅ Grupo creado: ID {$grupoId}\n\n";
    } else {
        $grupoId = $grupo->id;
        echo "✅ Grupo existente: ID {$grupoId}\n\n";
    }
    
    // Obtener bloques horarios
    $bloques = DB::table('bloques_horarios')->orderBy('numero_bloque')->take(6)->get();
    
    if ($bloques->isEmpty()) {
        echo "❌ No hay bloques horarios disponibles\n";
        exit(1);
    }
    
    echo "✅ Bloques horarios encontrados: " . $bloques->count() . "\n\n";
    
    // Crear horarios para Lunes, Miércoles y Viernes
    $dias = ['Lunes', 'Miércoles', 'Viernes'];
    $horariosCreados = 0;
    
    foreach ($dias as $index => $dia) {
        $bloque = $bloques[$index];
        
        // Verificar si ya existe (buscar en JSON)
        $existe = DB::table('horarios')
            ->where('id_grupo', $grupoId)
            ->where('id_bloque', $bloque->id)
            ->whereRaw("dias_semana::jsonb @> ?", [json_encode([$dia])])
            ->exists();
        
        if (!$existe) {
            DB::table('horarios')->insert([
                'id_grupo' => $grupoId,
                'id_docente' => $docente->id,
                'id_aula' => $aula->id,
                'id_bloque' => $bloque->id,
                'dias_semana' => json_encode([$dia]),  // JSON array con un día
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            echo "✅ Horario creado: {$dia} | Bloque {$bloque->numero_bloque} ({$bloque->hora_inicio} - {$bloque->hora_fin}) | Aula {$aula->nombre}\n";
            $horariosCreados++;
        } else {
            echo "⚠️  Horario ya existe: {$dia} | Bloque {$bloque->numero_bloque}\n";
        }
    }
    
    DB::commit();
    
    echo "\n=== RESUMEN ===\n";
    echo "Horarios creados: {$horariosCreados}\n";
    echo "Materia: {$materia->nombre}\n";
    echo "Docente: Orlando (ID: {$docente->id})\n";
    echo "Aula: {$aula->nombre}\n";
    echo "\n✅ HORARIOS CREADOS EXITOSAMENTE\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
