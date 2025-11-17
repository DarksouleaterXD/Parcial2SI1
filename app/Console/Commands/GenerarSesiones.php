<?php

namespace App\Console\Commands;

use App\Models\Horario;
use App\Models\Sesion;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerarSesiones extends Command
{
    protected $signature = 'sesiones:generar {--semanas=2 : Número de semanas a generar}';
    protected $description = 'Genera sesiones basadas en los horarios para las próximas semanas';

    public function handle()
    {
        $semanas = $this->option('semanas');
        $this->info("Generando sesiones para las próximas {$semanas} semanas...");

        $horarios = Horario::with(['bloque', 'docente', 'grupo', 'aula'])->get();
        $this->info("Horarios encontrados: " . $horarios->count());

        $diasSemana = [
            'Lunes' => Carbon::MONDAY,
            'Martes' => Carbon::TUESDAY,
            'Miércoles' => Carbon::WEDNESDAY,
            'Miércoles' => Carbon::WEDNESDAY, // Variante
            'Jueves' => Carbon::THURSDAY,
            'Viernes' => Carbon::FRIDAY,
            'Sábado' => Carbon::SATURDAY,
            'Sabado' => Carbon::SATURDAY, // Sin acento
            'Domingo' => Carbon::SUNDAY,
        ];

        $sesionesCreadas = 0;
        $hoy = Carbon::today();

        foreach ($horarios as $horario) {
            if (!$horario->dias_semana || !is_array($horario->dias_semana)) {
                continue;
            }

            foreach ($horario->dias_semana as $dia) {
                $diaKey = ucfirst(strtolower(trim($dia)));

                if (!isset($diasSemana[$diaKey])) {
                    $this->warn("Día no reconocido: {$dia}");
                    continue;
                }

                $diaSemanaNum = $diasSemana[$diaKey];

                // Generar sesiones para las próximas semanas
                for ($i = 0; $i < $semanas; $i++) {
                    $fecha = $hoy->copy()->addWeeks($i)->next($diaSemanaNum);

                    // Verificar si ya existe la sesión
                    $existente = Sesion::where('horario_id', $horario->id)
                        ->where('fecha', $fecha->format('Y-m-d'))
                        ->exists();

                    if (!$existente) {
                        $hora_inicio = $horario->bloque->hora_inicio;
                        $inicio = Carbon::parse($hora_inicio);
                        $ventana_inicio = $inicio->copy()->subMinutes(30)->format('H:i:s');
                        $ventana_fin = $inicio->copy()->addMinutes(20)->format('H:i:s');

                        Sesion::create([
                            'horario_id' => $horario->id,
                            'docente_id' => $horario->id_docente,
                            'aula_id' => $horario->id_aula,
                            'grupo_id' => $horario->id_grupo,
                            'fecha' => $fecha->format('Y-m-d'),
                            'hora_inicio' => $hora_inicio,
                            'hora_fin' => $horario->bloque->hora_fin,
                            'ventana_inicio' => $ventana_inicio,
                            'ventana_fin' => $ventana_fin,
                            'estado' => 'programada',
                            'activo' => true,
                        ]);

                        $sesionesCreadas++;
                    }
                }
            }
        }

        $this->info("✓ Sesiones creadas: {$sesionesCreadas}");
        return 0;
    }
}
