<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Horario;

echo "=== VERIFICANDO SOFT DELETES EN HORARIOS ===\n\n";

// Con trashed
$horariosConTrashed = Horario::withTrashed()->where('id_docente', 7)->get();
echo "Horarios con soft deletes: " . $horariosConTrashed->count() . "\n\n";

foreach ($horariosConTrashed as $h) {
    echo "ID: {$h->id}, deleted_at: " . ($h->deleted_at ?? 'NULL') . "\n";
}

echo "\n";

// Sin trashed (normal)
$horariosNormales = Horario::where('id_docente', 7)->get();
echo "Horarios normales (sin trashed): " . $horariosNormales->count() . "\n\n";

foreach ($horariosNormales as $h) {
    echo "ID: {$h->id}\n";
}
