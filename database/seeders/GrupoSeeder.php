<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener primeros periodos creados
        $periodo1 = Periodo::where('nombre', '2025-1')->first();
        $periodo2 = Periodo::where('nombre', '2025-2')->first();

        // Obtener algunas materias (si existen)
        $materias = Materia::limit(3)->get();

        if (!$periodo1 || !$periodo2 || $materias->isEmpty()) {
            $this->command->info('Se requieren al menos 1 Periodo y 1 Materia para crear grupos.');
            return;
        }

        $grupos = [
            // Grupos para Periodo 2025-1
            [
                'id_materia' => $materias->first()->id,
                'id_periodo' => $periodo1->id,
                'paralelo' => 'A',
                'turno' => 'mañana',
                'capacidad' => 40,
            ],
            [
                'id_materia' => $materias->first()->id,
                'id_periodo' => $periodo1->id,
                'paralelo' => 'B',
                'turno' => 'tarde',
                'capacidad' => 35,
            ],
            [
                'id_materia' => $materias->count() > 1 ? $materias[1]->id : $materias->first()->id,
                'id_periodo' => $periodo1->id,
                'paralelo' => 'A',
                'turno' => 'mañana',
                'capacidad' => 45,
            ],
            [
                'id_materia' => $materias->count() > 2 ? $materias[2]->id : $materias->first()->id,
                'id_periodo' => $periodo1->id,
                'paralelo' => 'A',
                'turno' => 'noche',
                'capacidad' => 30,
            ],
            // Grupos para Periodo 2025-2
            [
                'id_materia' => $materias->first()->id,
                'id_periodo' => $periodo2->id,
                'paralelo' => 'A',
                'turno' => 'tarde',
                'capacidad' => 38,
            ],
            [
                'id_materia' => $materias->count() > 1 ? $materias[1]->id : $materias->first()->id,
                'id_periodo' => $periodo2->id,
                'paralelo' => 'B',
                'turno' => 'mañana',
                'capacidad' => 42,
            ],
        ];

        foreach ($grupos as $grupo) {
            Grupo::updateOrCreate(
                [
                    'id_materia' => $grupo['id_materia'],
                    'id_periodo' => $grupo['id_periodo'],
                    'paralelo' => $grupo['paralelo'],
                ],
                $grupo
            );
        }

        $this->command->info('✅ ' . count($grupos) . ' grupos creados exitosamente.');
    }
}
