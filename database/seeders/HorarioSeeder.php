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
        $grupos = Grupo::with(['materia', 'docente'])->get();
        $aulas = Aulas::where('activo', true)->get();
        $bloques = BloqueHorario::where('activo', true)->orderBy('numero_bloque')->get();

        if ($grupos->isEmpty()) {
            $this->command->error('❌ No hay grupos disponibles. Ejecute primero GrupoSeeder.');
            return;
        }

        if ($aulas->isEmpty()) {
            $this->command->error('❌ No hay aulas disponibles. Ejecute primero AulasSeeder.');
            return;
        }

        if ($bloques->isEmpty()) {
            $this->command->error('❌ No hay bloques horarios disponibles. Ejecute primero BloqueHorarioSeeder.');
            return;
        }

        // Definir patrones de días para cada paralelo
        $patronesDias = [
            'SC' => [
                ['lunes', 'miercoles'],      // Teoría
                ['viernes'],                  // Práctica/Lab
            ],
            'SA' => [
                ['martes', 'jueves'],        // Teoría
                ['viernes'],                  // Práctica/Lab
            ],
        ];

        // Bloques preferidos por turno
        $bloquesMañana = $bloques->filter(function($b) {
            $hora = (int)substr($b->hora_inicio, 0, 2);
            return $hora >= 7 && $hora < 12;
        });

        $bloquesTarde = $bloques->filter(function($b) {
            $hora = (int)substr($b->hora_inicio, 0, 2);
            return $hora >= 12 && $hora < 18;
        });

        $horarios = [];
        $aulaIndex = 0;
        $contadorHorarios = 0;

        // Agrupar grupos por materia para organizar mejor
        $gruposPorMateria = $grupos->groupBy('id_materia');

        foreach ($gruposPorMateria as $idMateria => $gruposMateria) {
            foreach ($gruposMateria as $grupo) {
                $paralelo = $grupo->paralelo ?? 'A';
                $docente = $grupo->docente;

                if (!$docente) {
                    $this->command->warn("⚠️ Grupo {$grupo->id} no tiene docente asignado. Saltando...");
                    continue;
                }

                // Determinar patrón de días según paralelo
                $patron = $patronesDias[$paralelo] ?? $patronesDias['SC'];

                // Seleccionar bloques según índice (alternando mañana/tarde)
                $bloquesDisponibles = ($aulaIndex % 2 === 0) ? $bloquesMañana : $bloquesTarde;

                if ($bloquesDisponibles->isEmpty()) {
                    $bloquesDisponibles = $bloques;
                }

                // Crear horario para teoría (Lun/Mier o Mar/Jue)
                $bloqueTeoria = $bloquesDisponibles->random();
                $aulaTeoria = $aulas[$aulaIndex % $aulas->count()];

                $horarios[] = [
                    'id_grupo' => $grupo->id,
                    'id_aula' => $aulaTeoria->id,
                    'id_docente' => $docente->id,
                    'id_bloque' => $bloqueTeoria->id,
                    'dias_semana' => json_encode($patron[0]), // Días de teoría
                    'activo' => true,
                    'descripcion' => "Clases teóricas - {$grupo->materia->nombre} ({$paralelo})",
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $contadorHorarios++;
                $aulaIndex++;

                // Crear horario para práctica/laboratorio (Viernes)
                $bloquePractica = $bloquesDisponibles->random();
                $aulaPractica = $aulas[$aulaIndex % $aulas->count()];

                $horarios[] = [
                    'id_grupo' => $grupo->id,
                    'id_aula' => $aulaPractica->id,
                    'id_docente' => $docente->id,
                    'id_bloque' => $bloquePractica->id,
                    'dias_semana' => json_encode($patron[1]), // Día de práctica
                    'activo' => true,
                    'descripcion' => "Práctica/Laboratorio - {$grupo->materia->nombre} ({$paralelo})",
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $contadorHorarios++;
                $aulaIndex++;

                $nombreMateria = $grupo->materia->nombre ?? "Materia {$grupo->id_materia}";
                $nombreDocente = $docente->persona->nombre ?? "Docente {$docente->id}";
                $diasTeoria = implode(', ', $patron[0]);
                $diasPractica = implode(', ', $patron[1]);

                $this->command->info("✅ Horarios creados para: {$nombreMateria} - {$paralelo}");
                $this->command->info("   └─ Teoría: {$diasTeoria} | Bloque: {$bloqueTeoria->nombre} | Aula: {$aulaTeoria->codigo}");
                $this->command->info("   └─ Práctica: {$diasPractica} | Bloque: {$bloquePractica->nombre} | Aula: {$aulaPractica->codigo}");
            }
        }

        // Insertar horarios en la base de datos
        if (!empty($horarios)) {
            // Limpiar horarios existentes (opcional)
            Horario::truncate();

            foreach (array_chunk($horarios, 50) as $chunk) {
                foreach ($chunk as $horario) {
                    Horario::create($horario);
                }
            }
        }

        $this->command->info('');
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ Total: {$contadorHorarios} horarios creados exitosamente");
        $this->command->info("📚 Grupos procesados: {$grupos->count()}");
        $this->command->info("🏫 Aulas utilizadas: {$aulas->count()}");
        $this->command->info("⏰ Bloques disponibles: {$bloques->count()}");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }
}
