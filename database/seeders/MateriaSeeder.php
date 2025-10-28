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
        // Obtener una carrera existente o crear una
        $carrera = Carrera::first();
        if (!$carrera) {
            $carrera = Carrera::create([
                'nombre' => 'Ingeniería en Sistemas',
                'codigo' => 'IS',
                'activo' => true,
            ]);
        }

        $materias = [
            [
                'codigo' => 'IS101',
                'nombre' => 'Fundamentos de Programación',
                'carrera_id' => $carrera->id,
                'horas_semana' => 4,
                'activo' => true,
            ],
            [
                'codigo' => 'IS102',
                'nombre' => 'Bases de Datos',
                'carrera_id' => $carrera->id,
                'horas_semana' => 4,
                'activo' => true,
            ],
            [
                'codigo' => 'IS103',
                'nombre' => 'Redes de Computadoras',
                'carrera_id' => $carrera->id,
                'horas_semana' => 3,
                'activo' => true,
            ],
        ];

        foreach ($materias as $materia) {
            Materia::updateOrCreate(
                ['codigo' => $materia['codigo']],
                $materia
            );
        }

        $this->command->info('✅ Materias creadas exitosamente.');
    }
}
