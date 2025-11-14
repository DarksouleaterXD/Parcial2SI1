<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$kevinUser = App\Models\User::where('email', 'kevin@gmail.com')->first();

if (!$kevinUser) {
    echo "No se encontró usuario Kevin\n";
    exit;
}

echo "Usuario Kevin:\n";
echo "ID: {$kevinUser->id}\n";
echo "Email: {$kevinUser->email}\n\n";

$persona = $kevinUser->persona;
if ($persona) {
    echo "Persona:\n";
    echo "ID: {$persona->id}\n";
    echo "Nombres: {$persona->nombres}\n";
    echo "Apellido Paterno: {$persona->apellido_paterno}\n";
    echo "Apellido Materno: {$persona->apellido_materno}\n\n";

    $docente = App\Models\Docente::where('id_persona', $persona->id)->first();
    if ($docente) {
        echo "Docente:\n";
        echo "ID: {$docente->id}\n";
        echo "Activo: " . ($docente->activo ? 'Sí' : 'No') . "\n\n";

        // Buscar horarios
        $hoy = now();
        echo "Hoy es: " . $hoy->format('Y-m-d') . " (" . $hoy->locale('es')->dayName . ")\n\n";

        $horarios = App\Models\Horario::where('id_docente', $docente->id)->with('grupo.materia', 'bloque')->get();
        echo "Horarios: " . $horarios->count() . "\n";
        foreach ($horarios as $horario) {
            echo "  - Horario ID: {$horario->id}\n";
            echo "    Materia: {$horario->grupo->materia->nombre}\n";
            echo "    Grupo: {$horario->grupo->nombre}\n";
            echo "    Días: " . json_encode($horario->dias_semana) . "\n";
            echo "    Bloque: {$horario->bloque->hora_inicio} - {$horario->bloque->hora_fin}\n";

            // Buscar sesiones de este horario para hoy
            $sesiones = App\Models\Sesion::where('horario_id', $horario->id)
                ->where('fecha', $hoy->format('Y-m-d'))
                ->get();
            echo "    Sesiones hoy: " . $sesiones->count() . "\n";
            foreach ($sesiones as $sesion) {
                echo "      - Sesion ID: {$sesion->id} | Hora: {$sesion->hora_inicio} - {$sesion->hora_fin}\n";
            }
            echo "\n";
        }
    } else {
        echo "Kevin NO es docente\n";
    }
} else {
    echo "Kevin no tiene persona asociada\n";
}
