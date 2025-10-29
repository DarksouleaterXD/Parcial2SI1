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
        $bloques = [
            [
                'nombre' => 'Bloque 1',
                'hora_inicio' => '07:00',
                'hora_fin' => '08:30',
                'numero_bloque' => 1,
                'activo' => true,
            ],
            [
                'nombre' => 'Bloque 2',
                'hora_inicio' => '08:40',
                'hora_fin' => '10:10',
                'numero_bloque' => 2,
                'activo' => true,
            ],
            [
                'nombre' => 'Bloque 3',
                'hora_inicio' => '10:20',
                'hora_fin' => '11:50',
                'numero_bloque' => 3,
                'activo' => true,
            ],
            [
                'nombre' => 'Bloque 4',
                'hora_inicio' => '12:30',
                'hora_fin' => '14:00',
                'numero_bloque' => 4,
                'activo' => true,
            ],
            [
                'nombre' => 'Bloque 5',
                'hora_inicio' => '14:10',
                'hora_fin' => '15:40',
                'numero_bloque' => 5,
                'activo' => true,
            ],
            [
                'nombre' => 'Bloque 6',
                'hora_inicio' => '15:50',
                'hora_fin' => '17:20',
                'numero_bloque' => 6,
                'activo' => true,
            ],
        ];

        foreach ($bloques as $bloque) {
            BloqueHorario::create($bloque);
        }
    }
}
