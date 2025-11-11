<?php

/**
 * Script de prueba para los endpoints de importación de usuarios
 * Ejecutar: php test_importacion_usuarios.php
 */

$API_URL = "http://127.0.0.1:8000/api";

// Credenciales de admin
$credentials = [
    'email' => 'admin@gestion.com',
    'password' => 'admin123'
];

echo "\n=== TEST: IMPORTACIÓN DE USUARIOS EN LOTE ===\n\n";

// 1. Login
echo "1. Login como admin...\n";
$ch = curl_init("$API_URL/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($credentials));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$data = json_decode($response, true);

if (!isset($data['token'])) {
    die("❌ Error en login: " . ($data['message'] ?? 'Sin respuesta') . "\n\n");
}

$token = $data['token'];
echo "✓ Token obtenido\n\n";

// 2. Descargar plantilla
echo "2. Descargando plantilla Excel...\n";
$ch = curl_init("$API_URL/usuarios/importar/plantilla?formato=xlsx");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
]);
$plantilla = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode === 200) {
    file_put_contents('plantilla_usuarios.xlsx', $plantilla);
    echo "✓ Plantilla descargada: plantilla_usuarios.xlsx\n\n";
} else {
    echo "❌ Error al descargar plantilla (HTTP $httpCode)\n\n";
}

// 3. Crear archivo CSV de prueba
echo "3. Creando archivo CSV de prueba...\n";
$csvContent = <<<CSV
ci,nombre,apellido_paterno,apellido_materno,email,telefono,rol,fecha_nacimiento
11111111,María,González,Silva,maria.gonzalez@test.com,70111111,docente,1985-03-20
22222222,Pedro,Rodríguez,Castro,pedro.rodriguez@test.com,70222222,coordinador,1980-07-15
33333333,Ana,Martínez,López,ana.martinez@test.com,70333333,docente,1990-11-30
12345678,Carlos,Duplicate,Test,duplicate@test.com,70444444,docente,1988-05-10
12345678,Error,Duplicate,CI,error@test.com,70555555,docente,1995-01-01
44444444,Luis,Sin,Email,,70666666,docente,1992-08-25
55555555,Carmen,Rol,Invalido,carmen@test.com,70777777,superusuario,1987-12-05
CSV;

file_put_contents('test_usuarios.csv', $csvContent);
echo "✓ Archivo creado: test_usuarios.csv\n\n";

// 4. Validar archivo
echo "4. Validando archivo CSV...\n";
$ch = curl_init("$API_URL/usuarios/importar/validar");
$cfile = new CURLFile(realpath('test_usuarios.csv'), 'text/csv', 'test_usuarios.csv');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['archivo' => $cfile]);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
]);
$response = curl_exec($ch);
$data = json_decode($response, true);

echo "Respuesta de validación:\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if (isset($data['data']['estadisticas'])) {
    $stats = $data['data']['estadisticas'];
    echo "📊 Estadísticas:\n";
    echo "   Total filas: {$stats['total']}\n";
    echo "   Válidas: {$stats['validos']}\n";
    echo "   Con errores: {$stats['errores']}\n\n";

    if (isset($data['data']['resultados'])) {
        echo "📋 Detalle de filas con errores:\n";
        foreach ($data['data']['resultados'] as $resultado) {
            if (!$resultado['valido']) {
                echo "   Fila {$resultado['fila']}: " . implode(', ', $resultado['errores']) . "\n";
            }
        }
        echo "\n";
    }
}

// 5. Confirmar importación (solo si hay filas válidas)
if (isset($data['data']['estadisticas']['validos']) && $data['data']['estadisticas']['validos'] > 0) {
    echo "5. Confirmando importación de filas válidas...\n";

    $ch = curl_init("$API_URL/usuarios/importar/confirmar");
    $cfile = new CURLFile(realpath('test_usuarios.csv'), 'text/csv', 'test_usuarios.csv');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'archivo' => $cfile,
        'generar_passwords' => '1',
        'enviar_emails' => '0',
    ]);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
    ]);
    $response = curl_exec($ch);
    $data = json_decode($response, true);

    echo "Respuesta de confirmación:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    if (isset($data['data']['usuarios_creados'])) {
        echo "✓ Usuarios creados:\n";
        foreach ($data['data']['usuarios_creados'] as $usuario) {
            echo "   - Email: {$usuario['email']}\n";
            echo "     Password: {$usuario['password']}\n";
        }
        echo "\n";
    }
} else {
    echo "⚠ No hay filas válidas para importar\n\n";
}

// 6. Ver historial
echo "6. Consultando historial de importaciones...\n";
$ch = curl_init("$API_URL/usuarios/importar/historial");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
]);
$response = curl_exec($ch);
$data = json_decode($response, true);

if (isset($data['data'])) {
    echo "📜 Últimas " . count($data['data']) . " importaciones:\n";
    foreach (array_slice($data['data'], 0, 5) as $entrada) {
        echo "   - {$entrada['created_at']}: {$entrada['operacion']} - {$entrada['descripcion']}\n";
    }
}

echo "\n=== TEST COMPLETADO ===\n\n";
