<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

// Inicializar la aplicación
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$request = \Illuminate\Http\Request::create('/api/carreras', 'GET');
$response = $kernel->handle($request);

echo "Status: " . $response->status() . "\n";
echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
echo "\nRespuesta:\n";
echo $response->getContent() . "\n";
