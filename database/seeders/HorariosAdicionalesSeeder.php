<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Horario;
use App\Models\Grupo;
use App\Models\Aulas;
use App\Models\BloqueHorario;

class HorariosAdicionalesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Obtener grupos que aún no tienen horarios completos
        $grupos = Grupo::whereNotIn('id', [2, 3])->get(); // Excluir los que ya tienen horario

        // Definir patrones de horarios
        $patrones = [
            // Teoría (2 días por semana)
            [
                'dias' => ['Lunes', 'Miércoles'],
                'tipo' => 'Teoría',
            ],
            [
                'dias' => ['Martes', 'Jueves'],
                'tipo' => 'Teoría',
            ],
            // Práctica (1 día por semana)
            [
                'dias' => ['Viernes'],
                'tipo' => 'Práctica',
            ],
        ];

        $aulas = Aulas::where('activo', true)->pluck('id')->toArray();
        $bloquesManana = BloqueHorario::whereIn('numero_bloque', [1, 2, 3, 4])->pluck('id')->toArray();
        $bloquesTarde = BloqueHorario::whereIn('numero_bloque', [5, 6, 7, 8])->pluck('id')->toArray();

        $horariosCreados = 0;
        $maxHorarios = 10;

        foreach ($grupos as $grupo) {
            if ($horariosCreados >= $maxHorarios) {
                break;
            }

            // Asignar horario de teoría
            if ($horariosCreados < $maxHorarios) {
                $patron = $patrones[array_rand([0, 1])]; // Aleatorio entre Lun/Mie o Mar/Jue
                $aula = $aulas[array_rand($aulas)];
                $bloque = ($grupo->id % 2 == 0) ? $bloquesManana[array_rand($bloquesManana)] : $bloquesTarde[array_rand($bloquesTarde)];

                // Verificar que no haya conflicto
                $conflicto = $this->verificarConflicto($aula, $bloque, $patron['dias']);

                if (!$conflicto) {
                    Horario::create([
                        'id_grupo' => $grupo->id,
                        'id_aula' => $aula,
                        'id_docente' => $grupo->id_docente,
                        'id_bloque' => $bloque,
                        'dias_semana' => $patron['dias'],
                        'activo' => true,
                        'descripcion' => $patron['tipo'] . ' - ' . $grupo->codigo,
                    ]);

                    $horariosCreados++;
                    echo "✅ Horario {$horariosCreados} creado: Grupo {$grupo->codigo} - {$patron['tipo']} - " . implode('/', $patron['dias']) . "\n";
                }
            }

            // Asignar horario de práctica
            if ($horariosCreados < $maxHorarios) {
                $patron = $patrones[2]; // Viernes
                $aula = $aulas[array_rand($aulas)];
                $bloque = ($grupo->id % 2 == 0) ? $bloquesManana[array_rand($bloquesManana)] : $bloquesTarde[array_rand($bloquesTarde)];

                $conflicto = $this->verificarConflicto($aula, $bloque, $patron['dias']);

                if (!$conflicto) {
                    Horario::create([
                        'id_grupo' => $grupo->id,
                        'id_aula' => $aula,
                        'id_docente' => $grupo->id_docente,
                        'id_bloque' => $bloque,
                        'dias_semana' => $patron['dias'],
                        'activo' => true,
                        'descripcion' => $patron['tipo'] . ' - ' . $grupo->codigo,
                    ]);

                    $horariosCreados++;
                    echo "✅ Horario {$horariosCreados} creado: Grupo {$grupo->codigo} - {$patron['tipo']} - " . implode('/', $patron['dias']) . "\n";
                }
            }
        }

        echo "\n🎉 Total de horarios adicionales creados: {$horariosCreados}\n";
    }

    /**
     * Verificar si hay conflicto de horario
     */
    private function verificarConflicto($aula, $bloque, $dias)
    {
        foreach ($dias as $dia) {
            $existe = Horario::where('id_aula', $aula)
                ->where('id_bloque', $bloque)
                ->whereJsonContains('dias_semana', $dia)
                ->where('activo', true)
                ->exists();

            if ($existe) {
                return true;
            }
        }

        return false;
    }
}
