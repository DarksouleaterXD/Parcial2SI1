<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\User;
use Laravel\Sanctum\HasApiTokens;

// Obtener el usuario admin
$user = User::where('email', 'admin@gestion.com')->first();

if (!$user) {
    echo "❌ No se encontró usuario admin\n";
    exit(1);
}

// Crear token
$token = $user->createToken('test-token')->plainTextToken;

echo "Token generado: " . substr($token, 0, 20) . "...\n\n";

// Hacer request al endpoint
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/api/docentes/1/estado',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode(['estado' => 'activo']),
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo $response . "\n\n";

// Intentar parsear JSON
if (json_last_error() === JSON_ERROR_NONE) {
    $data = json_decode($response, true);
    echo "✅ JSON válido\n";
    print_r($data);
} else {
    echo "❌ JSON inválido: " . json_last_error_msg() . "\n";
    echo "Primeros 200 caracteres: " . substr($response, 0, 200) . "\n";
}
