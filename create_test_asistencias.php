<?php

/**
 * Script para crear asistencias de prueba para CU16
 * Genera sesiones y asistencias sin validar para testing
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sesion;
use App\Models\Asistencia;
use App\Models\Horario;
use App\Models\Docente;
use App\Models\Aula;
use App\Models\Grupo;
use Carbon\Carbon;

echo "=== CREAR ASISTENCIAS DE PRUEBA ===\n\n";

try {
    // 1. Obtener un horario activo
    $horario = Horario::with(['docente', 'aula', 'grupo'])
        ->where('activo', true)
        ->first();

    if (!$horario) {
        echo "❌ No se encontró ningún horario activo. Primero crea horarios.\n";
        exit(1);
    }

    echo "✅ Horario encontrado: {$horario->id}\n";
    echo "   Docente: {$horario->docente->persona->nombre}\n";
    echo "   Materia: {$horario->grupo->materia->nombre}\n";
    echo "   Grupo: {$horario->grupo->paralelo}\n\n";

    // 2. Crear sesiones para hoy si no existen
    $hoy = Carbon::today();

    $sesion = Sesion::where('horario_id', $horario->id)
        ->where('fecha', $hoy)
        ->first();

    if (!$sesion) {
        echo "📅 Creando sesión para hoy...\n";

        $sesion = Sesion::create([
            'horario_id' => $horario->id,
            'docente_id' => $horario->id_docente, // ← Usar id_docente del horario
            'aula_id' => $horario->id_aula, // ← Usar id_aula del horario
            'grupo_id' => $horario->id_grupo, // ← Usar id_grupo del horario
            'fecha' => $hoy,
            'hora_inicio' => $horario->hora_inicio,
            'hora_fin' => $horario->hora_fin,
            'estado' => 'programada',
            'ventana_inicio' => Carbon::parse($horario->hora_inicio)->subMinutes(15)->format('H:i:s'),
            'ventana_fin' => Carbon::parse($horario->hora_inicio)->addMinutes(30)->format('H:i:s'),
        ]);

        echo "✅ Sesión creada: ID {$sesion->id}\n\n";
    } else {
        echo "✅ Sesión ya existe para hoy: ID {$sesion->id}\n\n";
    }

    // 3. Crear asistencias de prueba (SIN VALIDAR)
    echo "📝 Creando asistencias de prueba...\n\n";

    $asistenciasCreadas = 0;
    $estadosPosibles = ['presente', 'retardo', 'ausente', 'justificado'];
    $metodosPosibles = ['formulario', 'qr'];

    // Crear 3 asistencias de prueba con diferentes estados
    for ($i = 0; $i < 3; $i++) {
        // Verificar si ya existe
        $existe = Asistencia::where('sesion_id', $sesion->id)
            ->where('docente_id', $horario->docente_id)
            ->exists();

        if ($existe && $i === 0) {
            echo "⚠️  Ya existe una asistencia para esta sesión\n";

            // Actualizar para que NO esté validada
            Asistencia::where('sesion_id', $sesion->id)
                ->where('docente_id', $horario->id_docente)
                ->update(['validado' => false, 'validado_por' => null, 'validado_at' => null]);

            echo "✅ Asistencia marcada como NO validada\n\n";
            continue;
        }

        if ($i > 0) {
            // Para crear más asistencias, necesitamos más sesiones
            // Crear sesión para ayer
            $fecha = Carbon::today()->subDays($i);

            $sesionExtra = Sesion::where('horario_id', $horario->id)
                ->where('fecha', $fecha)
                ->first();

            if (!$sesionExtra) {
                $sesionExtra = Sesion::create([
                    'horario_id' => $horario->id,
                    'docente_id' => $horario->id_docente,
                    'aula_id' => $horario->id_aula,
                    'grupo_id' => $horario->id_grupo,
                    'fecha' => $fecha,
                    'hora_inicio' => $horario->hora_inicio,
                    'hora_fin' => $horario->hora_fin,
                    'estado' => 'programada',
                    'ventana_inicio' => Carbon::parse($horario->hora_inicio)->subMinutes(15)->format('H:i:s'),
                    'ventana_fin' => Carbon::parse($horario->hora_inicio)->addMinutes(30)->format('H:i:s'),
                ]);

                echo "📅 Sesión creada para {$fecha->format('Y-m-d')}: ID {$sesionExtra->id}\n";
            }

            $sesion = $sesionExtra;
        }

        $estado = $estadosPosibles[$i % count($estadosPosibles)];
        $metodo = $metodosPosibles[$i % count($metodosPosibles)];

        // Hora de marcado: dentro de la ventana o con retardo
        $horaMarcado = $estado === 'retardo'
            ? Carbon::parse($sesion->hora_inicio)->addMinutes(20)->format('H:i:s')
            : Carbon::parse($sesion->hora_inicio)->subMinutes(5)->format('H:i:s');

        $asistencia = Asistencia::create([
            'sesion_id' => $sesion->id,
            'docente_id' => $horario->id_docente,
            'estado' => $estado,
            'metodo_registro' => $metodo,
            'marcado_at' => Carbon::parse($sesion->fecha->format('Y-m-d') . ' ' . $horaMarcado),
            'hora_marcado' => $horaMarcado,
            'observacion' => "Asistencia de prueba - Estado: {$estado}",
            'evidencia_url' => null,
            'ip_marcado' => '127.0.0.1',
            'geolocalizacion' => null,
            'validado' => false, // ← IMPORTANTE: Sin validar
            'validado_por' => null,
            'validado_at' => null,
            'observacion_validacion' => null,
        ]);

        $asistenciasCreadas++;
        echo "✅ Asistencia #{$asistenciasCreadas} creada:\n";
        echo "   Fecha: {$sesion->fecha}\n";
        echo "   Estado: {$estado}\n";
        echo "   Método: {$metodo}\n";
        echo "   Hora marcado: {$horaMarcado}\n";
        echo "   Validado: NO ❌\n\n";
    }

    echo "\n=== RESUMEN ===\n";
    echo "✅ Total asistencias creadas/actualizadas: {$asistenciasCreadas}\n";
    echo "✅ Todas están SIN VALIDAR (pendientes)\n\n";

    // Verificar pendientes
    $pendientes = Asistencia::where('validado', false)->count();
    echo "📊 Total asistencias pendientes de validación: {$pendientes}\n\n";

    echo "🎉 ¡Listo! Ahora puedes probar el endpoint de validación.\n";
    echo "   GET /api/asistencias/pendientes\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
