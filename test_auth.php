<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

echo "═══════════════════════════════════════════════════════════════\n";
echo "           🧪 TEST DE AUTENTICACIÓN CON SANCTUM\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// TEST 1: LOGIN
echo "📝 TEST 1: POST /api/login (Login de superadmin)\n";
$payload = json_encode([
    'email' => 'admin@gestion.com',
    'password' => 'Admin123456'
]);

$request = \Illuminate\Http\Request::create('/api/login', 'POST', [], [], [],
    ['CONTENT_TYPE' => 'application/json'], $payload);
$response = $kernel->handle($request);
$status = $response->status();
$content = json_decode($response->getContent(), true);

echo "   Status: $status\n";
if ($status === 200 && $content['success']) {
    echo "   ✅ Login exitoso\n";
    $token = $content['data']['token'];
    echo "   Token: " . substr($token, 0, 20) . "...\n";
    echo "   Rol: " . $content['data']['rol'] . "\n";
    echo "   Is Admin: " . ($content['data']['is_admin'] ? 'Sí' : 'No') . "\n";
} else {
    echo "   ❌ Error en login\n";
    echo "   " . json_encode($content) . "\n";
}

// TEST 2: OBTENER PERFIL (requiere token)
echo "\n📖 TEST 2: GET /api/perfil (Obtener perfil del usuario autenticado)\n";
if (isset($token)) {
    $request = \Illuminate\Http\Request::create('/api/perfil', 'GET', [], [], [],
        [
            'CONTENT_TYPE' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ]);
    $response = $kernel->handle($request);
    $status = $response->status();
    $content = json_decode($response->getContent(), true);

    echo "   Status: $status\n";
    if ($status === 200 && $content['success']) {
        echo "   ✅ Perfil obtenido\n";
        echo "   Email: " . $content['data']['email'] . "\n";
        echo "   Rol: " . $content['data']['rol'] . "\n";
    } else {
        echo "   ❌ Error al obtener perfil\n";
    }
} else {
    echo "   ⚠️ Token no disponible\n";
}

// TEST 3: LOGIN CON CONTRASEÑA INCORRECTA
echo "\n❌ TEST 3: POST /api/login (Login fallido - contraseña incorrecta)\n";
$payload = json_encode([
    'email' => 'admin@gestion.com',
    'password' => 'WrongPassword123'
]);

$request = \Illuminate\Http\Request::create('/api/login', 'POST', [], [], [],
    ['CONTENT_TYPE' => 'application/json'], $payload);
$response = $kernel->handle($request);
$status = $response->status();
$content = json_decode($response->getContent(), true);

echo "   Status: $status\n";
if ($status !== 200) {
    echo "   ✅ Acceso rechazado correctamente\n";
    echo "   Mensaje: " . $content['message'] . "\n";
} else {
    echo "   ❌ Debería haber rechazado\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ PRUEBAS DE AUTENTICACIÓN COMPLETADAS\n";
echo "═══════════════════════════════════════════════════════════════\n";
