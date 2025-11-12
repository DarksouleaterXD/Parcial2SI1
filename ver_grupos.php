<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Grupo;

$grupos = Grupo::with(['materia', 'periodo', 'docente.persona'])
    ->whereNotNull('id_docente')
    ->orderBy('id')
    ->get();

echo "📋 GRUPOS ACTUALES CON DOCENTE\n";
echo str_repeat("=", 100) . "\n";
printf("%-5s | %-30s | %-12s | %-8s | %-10s | %-25s\n",
    "ID", "Materia", "Periodo", "Paralelo", "Capacidad", "Docente");
echo str_repeat("-", 100) . "\n";

foreach ($grupos as $g) {
    $docente_nombre = $g->docente->persona->nombre . ' ' .
                     ($g->docente->persona->apellido_paterno ?? '') . ' ' .
                     ($g->docente->persona->apellido_materno ?? '');

    printf("%-5s | %-30s | %-12s | %-8s | %-10s | %-25s\n",
        $g->id,
        $g->materia->nombre,
        $g->periodo->nombre,
        $g->paralelo,
        $g->capacidad . ' est.',
        trim($docente_nombre)
    );
}
