<?php

namespace Database\Seeders;

use App\Models\Horario;
use App\Models\Grupo;
use App\Models\Aulas;
use App\Models\Docente;
use App\Models\BloqueHorario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HorarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grupos = Grupo::all();
        $aulas = Aulas::all();
        $docentes = Docente::all();
        $bloques = BloqueHorario::all();
        $diasSemana = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes'];

        echo "Grupos: {$grupos->count()}, Aulas: {$aulas->count()}, Docentes: {$docentes->count()}, Bloques: {$bloques->count()}\n";

        if ($grupos->isEmpty() || $aulas->isEmpty() || $docentes->isEmpty() || $bloques->isEmpty()) {
            echo "Se requieren grupos, aulas, docentes y bloques de horarios previos.\n";
            return;
        }

        $horarios = [];
        $indiceAula = 0;
        $indiceDocente = 0;

        // Crear horarios para cada grupo
        foreach ($grupos as $index => $grupo) {
            // 2 bloques por grupo en días diferentes
            for ($i = 0; $i < 2; $i++) {
                $bloque = $bloques->random();
                $dia = $diasSemana[array_rand($diasSemana)];
                $aula = $aulas->skip($indiceAula % $aulas->count())->first();
                $docente = $docentes->skip($indiceDocente % $docentes->count())->first();

                if ($aula && $docente) {
                    $horarios[] = [
                        'id_grupo' => $grupo->id,
                        'id_aula' => $aula->id,
                        'id_docente' => $docente->id,
                        'id_bloque' => $bloque->id,
                        'dia_semana' => $dia,
                        'activo' => true,
                        'descripcion' => "Grupo {$grupo->id} - {$dia}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $indiceAula++;
                $indiceDocente++;
            }
        }

        // Insertar en chunks para evitar problemas
        if (!empty($horarios)) {
            foreach (array_chunk($horarios, 50) as $chunk) {
                Horario::insert($chunk);
            }
            echo "✅ " . count($horarios) . " horarios creados exitosamente.\n";
        }
    }
}
