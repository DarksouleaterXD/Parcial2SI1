<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "=== VERIFICACIÓN DE USUARIOS ===\n\n";
echo "Total usuarios: " . User::count() . "\n\n";

echo "Últimos 10 usuarios creados:\n";
echo str_repeat("-", 80) . "\n";

User::latest()->take(10)->get()->each(function($user) {
    echo sprintf(
        "ID: %-3d | %-25s | %-30s | %s\n",
        $user->id,
        $user->nombre,
        $user->email,
        $user->created_at->format('Y-m-d H:i:s')
    );
});

echo str_repeat("-", 80) . "\n";
