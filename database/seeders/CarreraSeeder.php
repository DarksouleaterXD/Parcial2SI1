<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Carrera;

class CarreraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carreras = [
            [
                'nombre' => 'Ingeniería en Sistemas',
                'codigo' => 'ISI',
                'sigla' => 'IS',
            ],
            [
                'nombre' => 'Ingeniería en Redes y Telecomunicaciones',
                'codigo' => 'IRTEL',
                'sigla' => 'RT',
            ],
            [
                'nombre' => 'Ingeniería Comercial',
                'codigo' => 'ICOM',
                'sigla' => 'IC',
            ],
            [
                'nombre' => 'Administración de Empresas',
                'codigo' => 'ADE',
                'sigla' => 'AE',
            ],
            [
                'nombre' => 'Contabilidad',
                'codigo' => 'CTB',
                'sigla' => 'CB',
            ],
        ];

        foreach ($carreras as $carrera) {
            Carrera::firstOrCreate(
                ['codigo' => $carrera['codigo']],
                $carrera
            );
        }
    }
}
