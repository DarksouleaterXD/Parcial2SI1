<?php

namespace Database\Seeders;

use App\Models\BloqueHorario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BloqueHorarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Primer conjunto: Bloques de 90 minutos (1.5 horas)
        $bloquesIntervalo90 = [];
        $horaInicio = new \DateTime('07:00');
        $horaFin = new \DateTime('22:45');
        $intervalo = new \DateInterval('PT90M'); // 90 minutos
        $numero = 1;

        while ($horaInicio < $horaFin) {
            $inicioBloque = clone $horaInicio;
            $horaInicio->add($intervalo);
            $finBloque = clone $horaInicio;

            if ($finBloque > $horaFin) {
                break;
            }

            $bloquesIntervalo90[] = [
                'numero_bloque' => $numero,
                'nombre' => "Bloque {$numero} (90min)",
                'hora_inicio' => $inicioBloque->format('H:i'),
                'hora_fin' => $finBloque->format('H:i'),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $numero++;
        }

        // Segundo conjunto: Bloques de 135 minutos (2.25 horas)
        $ultimoNumero = $numero; // Continuar numeración
        $bloquesIntervalo135 = [
            [
                'numero_bloque' => $ultimoNumero++,
                'nombre' => 'Bloque Mañana 1',
                'hora_inicio' => '07:00',
                'hora_fin' => '09:15',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'numero_bloque' => $ultimoNumero++,
                'nombre' => 'Bloque Mañana 2',
                'hora_inicio' => '09:15',
                'hora_fin' => '11:30',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'numero_bloque' => $ultimoNumero++,
                'nombre' => 'Bloque Mediodía',
                'hora_inicio' => '11:30',
                'hora_fin' => '13:45',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'numero_bloque' => $ultimoNumero++,
                'nombre' => 'Bloque Tarde 1',
                'hora_inicio' => '13:45',
                'hora_fin' => '16:00',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'numero_bloque' => $ultimoNumero++,
                'nombre' => 'Bloque Tarde 2',
                'hora_inicio' => '16:00',
                'hora_fin' => '18:15',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'numero_bloque' => $ultimoNumero++,
                'nombre' => 'Bloque Noche 1',
                'hora_inicio' => '18:15',
                'hora_fin' => '20:30',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'numero_bloque' => $ultimoNumero++,
                'nombre' => 'Bloque Noche 2',
                'hora_inicio' => '20:30',
                'hora_fin' => '22:45',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Combinar ambos conjuntos
        $todosBloques = array_merge($bloquesIntervalo90, $bloquesIntervalo135);

        // Insertar o actualizar todos los bloques
        foreach ($todosBloques as $bloque) {
            BloqueHorario::updateOrCreate(
                ['numero_bloque' => $bloque['numero_bloque']],
                $bloque
            );
        }

        $this->command->info('Se procesaron ' . count($todosBloques) . ' bloques de horario exitosamente.');
        $this->command->info('- Bloques de 90 minutos: ' . count($bloquesIntervalo90));
        $this->command->info('- Bloques de 135 minutos: ' . count($bloquesIntervalo135));
    }
}
