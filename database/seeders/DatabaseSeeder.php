<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar seeders en orden
        $this->call([
            MateriaSeeder::class,
            PeriodoSeeder::class,
            GrupoSeeder::class,
            AulasSeeder::class,
            DocenteSeeder::class,
            BloqueHorarioSeeder::class,
            HorarioSeeder::class,
        ]);
    }
}
