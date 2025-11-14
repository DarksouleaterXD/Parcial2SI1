<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Obtener la sesión de Kevin
$sesion = App\Models\Sesion::find(2);

if (!$sesion) {
    echo "❌ No se encontró la sesión ID 2\n";
    exit;
}

echo "Sesión actual:\n";
echo "ID: {$sesion->id}\n";
echo "Hora: {$sesion->hora_inicio} - {$sesion->hora_fin}\n";
echo "Ventana: {$sesion->ventana_inicio} - {$sesion->ventana_fin}\n\n";

// Calcular nueva hora (ahora + 10 minutos para estar en el inicio de ventana)
$ahora = now();
$nuevaHoraInicio = $ahora->copy()->addMinutes(10);
$nuevaHoraFin = $nuevaHoraInicio->copy()->addMinutes(90); // 1.5 horas de clase

echo "Actualizando a:\n";
echo "Hora actual: " . $ahora->format('H:i:s') . "\n";
echo "Nueva hora inicio: " . $nuevaHoraInicio->format('H:i:s') . "\n";
echo "Nueva hora fin: " . $nuevaHoraFin->format('H:i:s') . "\n\n";

// Actualizar la sesión
$sesion->hora_inicio = $nuevaHoraInicio->format('H:i:s');
$sesion->hora_fin = $nuevaHoraFin->format('H:i:s');
$sesion->save();

// Recalcular ventana
$sesion->calcularVentanaMarcado();
$sesion->save();

echo "✅ Sesión actualizada:\n";
echo "Hora: {$sesion->hora_inicio} - {$sesion->hora_fin}\n";
echo "Ventana: {$sesion->ventana_inicio} - {$sesion->ventana_fin}\n";
echo "\n";

// Verificar si está dentro de ventana
$dentroVentana = $sesion->dentroDeVentana();
echo "¿Dentro de ventana?: " . ($dentroVentana ? "✅ SÍ" : "❌ NO") . "\n";
