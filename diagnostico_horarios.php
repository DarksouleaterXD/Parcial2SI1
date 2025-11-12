<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Grupo;
use App\Models\Periodo;

echo "=== DIAGNÓSTICO: HORARIOS NO VISIBLES EN DOCENTE ===\n\n";

// 1. Verificar periodo vigente
echo "1. PERIODO VIGENTE:\n";
$periodoVigente = Periodo::where('vigente', true)->first();
if ($periodoVigente) {
    echo "   ✅ Periodo vigente encontrado: {$periodoVigente->nombre} (ID: {$periodoVigente->id})\n";
    echo "   Fechas: {$periodoVigente->fecha_inicio} - {$periodoVigente->fecha_fin}\n\n";
} else {
    echo "   ❌ NO HAY PERIODO VIGENTE\n\n";
}

// 2. Verificar docente Juan Pérez
echo "2. DOCENTE JUAN PÉREZ:\n";
$user = User::where('email', 'juan.perez@ejemplo.com')->first();
if (!$user) {
    echo "   ❌ Usuario no encontrado\n";
    exit;
}

$docente = Docente::where('id_persona', $user->id_persona)->first();
if (!$docente) {
    echo "   ❌ Docente no encontrado\n";
    exit;
}

echo "   ✅ Docente encontrado: {$docente->persona->nombre} (ID: {$docente->id})\n\n";

// 3. Verificar horarios del docente
echo "3. HORARIOS DEL DOCENTE:\n";
$horarios = Horario::where('id_docente', $docente->id)->with(['grupo', 'bloque'])->get();
echo "   Total horarios: {$horarios->count()}\n\n";

foreach ($horarios as $h) {
    echo "   - Horario ID: {$h->id}\n";
    echo "     Grupo ID: {$h->id_grupo}\n";
    
    if ($h->grupo) {
        echo "     Grupo Código: {$h->grupo->codigo}\n";
        echo "     Grupo Periodo ID: " . ($h->grupo->id_periodo ?? 'NULL') . "\n";
        
        if ($h->grupo->id_periodo && $periodoVigente && $h->grupo->id_periodo == $periodoVigente->id) {
            echo "     ✅ COINCIDE CON PERIODO VIGENTE\n";
        } else {
            echo "     ❌ NO COINCIDE CON PERIODO VIGENTE\n";
        }
    } else {
        echo "     ❌ GRUPO NO ENCONTRADO\n";
    }
    
    echo "     Bloque: " . ($h->bloque->nombre ?? 'N/A') . "\n";
    $dias = is_array($h->dias_semana) ? $h->dias_semana : (json_decode($h->dias_semana, true) ?? []);
    echo "     Días: " . implode(', ', $dias) . "\n";
    echo "\n";
}

// 4. Verificar grupos existentes
echo "4. GRUPOS EXISTENTES:\n";
$grupos = Grupo::all();
echo "   Total grupos: {$grupos->count()}\n\n";

foreach ($grupos as $g) {
    echo "   - Grupo ID: {$g->id}\n";
    echo "     Código: {$g->codigo}\n";
    echo "     Periodo ID: " . ($g->id_periodo ?? 'NULL') . "\n";
    echo "     Docente ID: " . ($g->id_docente ?? 'NULL') . "\n";
    echo "\n";
}

// 5. Verificar query que usa el endpoint
echo "5. SIMULACIÓN DEL QUERY DEL ENDPOINT:\n";
if ($periodoVigente) {
    $horariosQuery = Horario::where('id_docente', $docente->id)
        ->whereHas('grupo', function ($query) use ($periodoVigente) {
            $query->where('id_periodo', $periodoVigente->id);
        })
        ->get();
    
    echo "   Horarios filtrados por periodo vigente: {$horariosQuery->count()}\n";
    
    if ($horariosQuery->isEmpty()) {
        echo "   ❌ EL QUERY NO DEVUELVE HORARIOS\n";
        echo "   \n";
        echo "   PROBLEMA: Los grupos de los horarios no tienen el periodo vigente asignado\n";
    } else {
        echo "   ✅ El query devuelve horarios correctamente\n";
    }
}

echo "\n=== FIN DIAGNÓSTICO ===\n";
