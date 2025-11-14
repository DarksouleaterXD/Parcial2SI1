<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simular autenticación de Kevin
$user = App\Models\User::find(16);
auth()->login($user);

echo "Usuario autenticado: {$user->email}\n\n";

// Obtener el docente
$persona = $user->persona;
$docente = $persona ? App\Models\Docente::where('id_persona', $persona->id)->first() : null;

if (!$docente) {
    echo "❌ Usuario no tiene docente asociado\n";
    exit;
}

echo "Docente ID: {$docente->id}\n";
echo "Persona ID: {$persona->id}\n\n";

// Simular el controlador
$controller = new App\Http\Controllers\AsistenciaController();
$response = $controller->misClasesHoy();
$responseData = $response->getData(true);

echo "Respuesta del controlador:\n";
echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";
