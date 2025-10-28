<?php

namespace Database\Seeders;

use App\Models\Periodo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PeriodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periodos = [
            [
                'nombre' => '2025-1',
                'fecha_inicio' => Carbon::parse('2025-02-03'),
                'fecha_fin' => Carbon::parse('2025-05-30'),
                'activo' => true,
            ],
            [
                'nombre' => '2025-2',
                'fecha_inicio' => Carbon::parse('2025-06-02'),
                'fecha_fin' => Carbon::parse('2025-09-26'),
                'activo' => true,
            ],
            [
                'nombre' => '2024-2',
                'fecha_inicio' => Carbon::parse('2024-06-03'),
                'fecha_fin' => Carbon::parse('2024-09-27'),
                'activo' => false,
            ],
        ];

        foreach ($periodos as $periodo) {
            Periodo::updateOrCreate(
                ['nombre' => $periodo['nombre']],
                $periodo
            );
        }
    }
}
