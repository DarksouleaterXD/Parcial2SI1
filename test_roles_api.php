<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Primero hacer login
echo "=== LOGIN ===\n";
$loginData = [
    'email' => 'admin@ficct.com',
    'password' => 'admin123',
];
$loginRequest = \Illuminate\Http\Request::create('/api/login', 'POST', [], [], [],
    ['CONTENT_TYPE' => 'application/json'],
    json_encode($loginData)
);
$loginRequest->headers->set('Accept', 'application/json');

$loginResponse = $kernel->handle($loginRequest);
echo "Status: " . $loginResponse->status() . "\n";
$loginData = json_decode($loginResponse->getContent(), true);

if (isset($loginData['token'])) {
    $token = $loginData['token'];
    echo "Token obtenido: " . substr($token, 0, 20) . "...\n\n";

    // Probar el endpoint de roles
    echo "=== GET /api/admin/roles ===\n";
    $rolesRequest = \Illuminate\Http\Request::create('/api/admin/roles', 'GET');
    $rolesRequest->headers->set('Authorization', 'Bearer ' . $token);
    $rolesRequest->headers->set('Accept', 'application/json');

    $rolesResponse = $kernel->handle($rolesRequest);
    echo "Status: " . $rolesResponse->status() . "\n";
    echo "Content:\n";
    $rolesData = json_decode($rolesResponse->getContent(), true);
    echo json_encode($rolesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Probar el endpoint de permisos
    echo "=== GET /api/admin/roles/permisos ===\n";
    $permisosRequest = \Illuminate\Http\Request::create('/api/admin/roles/permisos', 'GET');
    $permisosRequest->headers->set('Authorization', 'Bearer ' . $token);
    $permisosRequest->headers->set('Accept', 'application/json');

    $permisosResponse = $kernel->handle($permisosRequest);
    echo "Status: " . $permisosResponse->status() . "\n";
    echo "Content:\n";
    $permisosData = json_decode($permisosResponse->getContent(), true);
    echo json_encode($permisosData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Error en login:\n";
    echo json_encode($loginData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
