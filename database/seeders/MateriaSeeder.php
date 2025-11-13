<?php

namespace Database\Seeders;

use App\Models\Materia;
use App\Models\Carrera;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MateriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener la carrera de Ingeniería en Sistemas (187-4)
        $carrera = Carrera::firstOrCreate(
            ['codigo' => '187-4'],
            [
                'nombre' => 'Ingeniería en Sistemas',
                'activo' => true,
            ]
        );

        $materias = [
            // Semestre 1
            ['codigo' => 'MAT101', 'nombre' => 'Cálculo I', 'horas_semana' => 4],
            ['codigo' => 'INF119', 'nombre' => 'Estructuras Discretas', 'horas_semana' => 4],
            ['codigo' => 'INF110', 'nombre' => 'Introducción a la Informática', 'horas_semana' => 4],
            ['codigo' => 'LIN100', 'nombre' => 'Inglés Técnico I', 'horas_semana' => 3],
            ['codigo' => 'FIS100', 'nombre' => 'Física I', 'horas_semana' => 4],

            // Semestre 2
            ['codigo' => 'MAT102', 'nombre' => 'Cálculo II', 'horas_semana' => 4],
            ['codigo' => 'MAT103', 'nombre' => 'Algebra Lineal', 'horas_semana' => 4],
            ['codigo' => 'INF120', 'nombre' => 'Programación I', 'horas_semana' => 4],
            ['codigo' => 'LIN101', 'nombre' => 'Inglés Técnico II', 'horas_semana' => 3],
            ['codigo' => 'FIS102', 'nombre' => 'Física II', 'horas_semana' => 4],

            // Semestre 3
            ['codigo' => 'MAT207', 'nombre' => 'Ecuaciones Diferenciales', 'horas_semana' => 4],
            ['codigo' => 'INF210', 'nombre' => 'Programación II', 'horas_semana' => 4],
            ['codigo' => 'INF211', 'nombre' => 'Arquitectura de Computadoras', 'horas_semana' => 4],
            ['codigo' => 'ADM100', 'nombre' => 'Administración', 'horas_semana' => 3],
            ['codigo' => 'FIS200', 'nombre' => 'Física III', 'horas_semana' => 4],

            // Semestre 4
            ['codigo' => 'MAT202', 'nombre' => 'Probabilidad y Estadística I', 'horas_semana' => 4],
            ['codigo' => 'MAT205', 'nombre' => 'Métodos Numéricos', 'horas_semana' => 4],
            ['codigo' => 'INF220', 'nombre' => 'Estructura de Datos I', 'horas_semana' => 4],
            ['codigo' => 'INF221', 'nombre' => 'Programación Ensamblador', 'horas_semana' => 4],
            ['codigo' => 'ADM200', 'nombre' => 'Contabilidad', 'horas_semana' => 3],

            // Semestre 5
            ['codigo' => 'MAT302', 'nombre' => 'Probabilidad y Estadística II', 'horas_semana' => 4],
            ['codigo' => 'INF310', 'nombre' => 'Estructura de Datos II', 'horas_semana' => 4],
            ['codigo' => 'ADM330', 'nombre' => 'Organización y Métodos', 'horas_semana' => 3],
            ['codigo' => 'INF312', 'nombre' => 'Base de Datos I', 'horas_semana' => 4],
            ['codigo' => 'ECO300', 'nombre' => 'Economía para la Gestión', 'horas_semana' => 3],

            // Semestre 6
            ['codigo' => 'MAT329', 'nombre' => 'Investigación Operativa I', 'horas_semana' => 4],
            ['codigo' => 'INF323', 'nombre' => 'Sistemas Operativos I', 'horas_semana' => 4],
            ['codigo' => 'ADM320', 'nombre' => 'Finanzas para la Empresa', 'horas_semana' => 3],
            ['codigo' => 'INF342', 'nombre' => 'Sistemas de Información I', 'horas_semana' => 4],
            ['codigo' => 'INF322', 'nombre' => 'Base de Datos II', 'horas_semana' => 4],

            // Semestre 7
            ['codigo' => 'MAT419', 'nombre' => 'Investigación Operativa II', 'horas_semana' => 4],
            ['codigo' => 'INF433', 'nombre' => 'Redes I', 'horas_semana' => 4],
            ['codigo' => 'INF413', 'nombre' => 'Sistemas Operativos II', 'horas_semana' => 4],
            ['codigo' => 'INF432', 'nombre' => 'Sistemas para el Soporte a la Toma de Decisiones', 'horas_semana' => 4],
            ['codigo' => 'INF412', 'nombre' => 'Sistemas de Información II', 'horas_semana' => 4],

            // Semestre 8
            ['codigo' => 'ECO449', 'nombre' => 'Preparación y Evaluación de Proyectos', 'horas_semana' => 4],
            ['codigo' => 'INF423', 'nombre' => 'Redes II', 'horas_semana' => 4],
            ['codigo' => 'INF462', 'nombre' => 'Auditoría Informática', 'horas_semana' => 4],
            ['codigo' => 'INF442', 'nombre' => 'Sistemas de Información Geográfica', 'horas_semana' => 4],
            ['codigo' => 'INF422', 'nombre' => 'Ingeniería de Software I', 'horas_semana' => 4],

            // Semestre 9 - Talleres de Grado
            ['codigo' => 'INF511', 'nombre' => 'Taller de Grado I', 'horas_semana' => 8],
            ['codigo' => 'INF512', 'nombre' => 'Ingeniería de Software II', 'horas_semana' => 4],
            ['codigo' => 'INF513', 'nombre' => 'Tecnología Web', 'horas_semana' => 4],
            ['codigo' => 'INF552', 'nombre' => 'Arquitectura de Software', 'horas_semana' => 4],

            // Modalidad de Licenciatura
            ['codigo' => 'GRL001', 'nombre' => 'Modalidad de Licenciatura', 'horas_semana' => 0],

            // Electivas de Semestre 5
            ['codigo' => 'ELC001', 'nombre' => 'Administración de Recursos Humanos', 'horas_semana' => 3],
            ['codigo' => 'ELC002', 'nombre' => 'Costos y Presupuestos', 'horas_semana' => 3],

            // Electivas de Semestre 6
            ['codigo' => 'ELC003', 'nombre' => 'Producción y Marketing', 'horas_semana' => 3],
            ['codigo' => 'ELC004', 'nombre' => 'Reingeniería', 'horas_semana' => 3],

            // Electivas de Semestre 7
            ['codigo' => 'ELC005', 'nombre' => 'Ingeniería de la Calidad', 'horas_semana' => 3],
            ['codigo' => 'ELC006', 'nombre' => 'Benchmarking', 'horas_semana' => 3],

            // Electivas de Semestre 8
            ['codigo' => 'ELC007', 'nombre' => 'Introducción a la Macroeconomía', 'horas_semana' => 3],
            ['codigo' => 'ELC008', 'nombre' => 'Legislación en Ciencias de la Computación', 'horas_semana' => 3],
        ];

        foreach ($materias as $materiaData) {
            Materia::updateOrCreate(
                ['codigo' => $materiaData['codigo']],
                [
                    'codigo' => $materiaData['codigo'],
                    'nombre' => $materiaData['nombre'],
                    'carrera_id' => $carrera->id,
                    'horas_semana' => $materiaData['horas_semana'],
                    'activo' => true,
                ]
            );
        }

        $this->command->info('✅ ' . count($materias) . ' materias de Ingeniería en Sistemas (187-4) creadas exitosamente.');
    }
}
