<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// TEST: GET PERFIL
$payload = json_encode([
    'email' => 'admin@gestion.com',
    'password' => 'Admin123456'
]);

$request = \Illuminate\Http\Request::create('/api/login', 'POST', [], [], [],
    ['CONTENT_TYPE' => 'application/json'], $payload);
$response = $kernel->handle($request);
$content = json_decode($response->getContent(), true);

if ($content['success']) {
    $token = $content['data']['token'];

    echo "Token obtenido: " . substr($token, 0, 20) . "...\n\n";

    $request = \Illuminate\Http\Request::create('/api/perfil', 'GET', [], [], [],
        [
            'CONTENT_TYPE' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ]);
    $response = $kernel->handle($request);
    $status = $response->status();
    $content = json_decode($response->getContent(), true);

    echo "Status: $status\n";
    echo "Response: " . json_encode($content, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Login failed\n";
}
