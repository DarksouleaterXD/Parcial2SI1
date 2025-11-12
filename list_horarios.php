<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== LISTADO DE HORARIOS ===\n\n";

$horarios = DB::table('horarios')->get();

echo "Total horarios: " . $horarios->count() . "\n\n";

foreach ($horarios as $h) {
    echo "ID: {$h->id}\n";
    echo "  - id_grupo: {$h->id_grupo}\n";
    echo "  - id_docente: " . ($h->id_docente ?? 'NULL') . "\n";
    echo "  - id_aula: {$h->id_aula}\n";
    echo "  - id_bloque: {$h->id_bloque}\n";
    echo "  - dias_semana: {$h->dias_semana}\n";
    echo "  - activo: " . ($h->activo ? 'true' : 'false') . "\n";
    echo "\n";
}
