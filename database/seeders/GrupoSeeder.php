<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Docente;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el periodo actual (2025-1 o crear uno si no existe)
        $periodo = Periodo::firstOrCreate(
            ['nombre' => '2025-1'],
            [
                'gestion' => '2025',
                'tipo' => 'semestral',
                'fecha_inicio' => '2025-01-15',
                'fecha_fin' => '2025-06-30',
                'activo' => true,
            ]
        );

        // Obtener 4 materias específicas (ajusta los códigos según tu seeder de materias)
        $materias = [
            Materia::where('codigo', 'INF210')->first(), // Programación II
            Materia::where('codigo', 'INF220')->first(), // Estructura de Datos I
            Materia::where('codigo', 'MAT207')->first(), // Ecuaciones Diferenciales
            Materia::where('codigo', 'INF211')->first(), // Arquitectura de Computadoras
        ];

        // Filtrar materias que existen
        $materias = array_filter($materias);

        if (empty($materias)) {
            $this->command->warn('⚠️ No se encontraron las materias especificadas. Usando las primeras 4 materias disponibles.');
            $materias = Materia::limit(4)->get()->toArray();
        }

        if (count($materias) < 4) {
            $this->command->warn('⚠️ Se encontraron menos de 4 materias. Se crearán grupos con las disponibles.');
        }

        // Obtener docentes disponibles
        $docentes = Docente::with('persona')->get();

        if ($docentes->count() < 8) {
            $this->command->warn('⚠️ Se necesitan al menos 8 docentes. Solo hay ' . $docentes->count() . ' disponibles.');
            if ($docentes->isEmpty()) {
                $this->command->error('❌ No hay docentes disponibles. Ejecute primero DocenteSeeder.');
                return;
            }
        }

        $grupos = [];
        $docenteIndex = 0;

        // Crear grupos para cada materia con paralelos SC y SA
        foreach ($materias as $index => $materia) {
            // Asegurar que tenemos un docente disponible
            $docenteSC = $docentes[$docenteIndex % $docentes->count()];
            $docenteIndex++;
            $docenteSA = $docentes[$docenteIndex % $docentes->count()];
            $docenteIndex++;

            // Grupo SC (Sistema de Computación)
            $grupoSC = [
                'id_materia' => $materia->id,
                'id_periodo' => $periodo->id,
                'id_docente' => $docenteSC->id,
                'paralelo' => 'SC',
                'capacidad' => 35,
                'codigo' => $materia->codigo . '-SC-' . $periodo->gestion,
            ];

            // Grupo SA (Sistema de Automatización)
            $grupoSA = [
                'id_materia' => $materia->id,
                'id_periodo' => $periodo->id,
                'id_docente' => $docenteSA->id,
                'paralelo' => 'SA',
                'capacidad' => 35,
                'codigo' => $materia->codigo . '-SA-' . $periodo->gestion,
            ];

            $grupos[] = $grupoSC;
            $grupos[] = $grupoSA;

            // Crear los grupos
            $createdSC = Grupo::updateOrCreate(
                [
                    'id_materia' => $grupoSC['id_materia'],
                    'id_periodo' => $grupoSC['id_periodo'],
                    'paralelo' => $grupoSC['paralelo'],
                ],
                $grupoSC
            );

            $createdSA = Grupo::updateOrCreate(
                [
                    'id_materia' => $grupoSA['id_materia'],
                    'id_periodo' => $grupoSA['id_periodo'],
                    'paralelo' => $grupoSA['paralelo'],
                ],
                $grupoSA
            );

            $nombreDocenteSC = $docenteSC->persona->nombre ?? 'Docente ' . $docenteSC->id;
            $nombreDocenteSA = $docenteSA->persona->nombre ?? 'Docente ' . $docenteSA->id;

            $this->command->info("✅ Grupo creado: {$materia->nombre} - SC (Docente: {$nombreDocenteSC})");
            $this->command->info("✅ Grupo creado: {$materia->nombre} - SA (Docente: {$nombreDocenteSA})");
        }

        $this->command->info('');
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ Total: " . count($grupos) . " grupos creados exitosamente");
        $this->command->info("📚 Materias: " . count($materias));
        $this->command->info("👥 Paralelos por materia: SC y SA");
        $this->command->info("📅 Periodo: {$periodo->nombre}");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }
}
