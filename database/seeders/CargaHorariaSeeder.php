<?php

namespace Database\Seeders;

use App\Models\CargaHoraria;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Periodo;
use Illuminate\Database\Seeder;

class CargaHorariaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $docentes = Docente::all();
        $grupos = Grupo::all();
        $periodos = Periodo::where('activo', true)->get();

        if ($docentes->isEmpty() || $grupos->isEmpty() || $periodos->isEmpty()) {
            echo "Se requieren docentes, grupos y periodos activos previos.\n";
            return;
        }

        $horasPorDocente = [];
        $cargas = [];

        // Asignar docentes a grupos con horas/semana
        foreach ($grupos as $grupo) {
            // Cada grupo tendrá 1-2 docentes asignados
            $docentesParaGrupo = $docentes->random(min(2, $docentes->count()));

            foreach ($docentesParaGrupo as $docente) {
                foreach ($periodos as $periodo) {
                    // Evitar duplicados y validar unicidad
                    $key = "{$docente->id}_{$grupo->id}_{$periodo->id}";

                    if (!isset($horasPorDocente[$key])) {
                        $horas = rand(3, 6); // Entre 3 y 6 horas/semana

                        $cargas[] = [
                            'id_docente' => $docente->id,
                            'id_grupo' => $grupo->id,
                            'id_periodo' => $periodo->id,
                            'horas_semana' => $horas,
                            'observaciones' => "Carga horaria base para {$grupo->id}",
                            'activo' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $horasPorDocente[$key] = $horas;
                    }
                }
            }
        }

        // Insertar en chunks
        if (!empty($cargas)) {
            foreach (array_chunk($cargas, 50) as $chunk) {
                CargaHoraria::insert($chunk);
            }
            echo "✅ " . count($cargas) . " cargas horarias creadas exitosamente.\n";
        }
    }
}
