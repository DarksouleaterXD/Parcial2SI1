<?php

namespace Database\Seeders;

use App\Models\Aulas;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AulasSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aulas = [
            // Primer Piso (Aulas 10-15)
            [
                'codigo' => 'AULA-10',
                'nombre' => 'Aula 10',
                'numero_aula' => 10,
                'tipo' => 'teorica',
                'capacidad' => 40,
                'ubicacion' => 'Edificio FICCT, Piso 1',
                'piso' => 1,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-11',
                'nombre' => 'Aula 11',
                'numero_aula' => 11,
                'tipo' => 'teorica',
                'capacidad' => 40,
                'ubicacion' => 'Edificio FICCT, Piso 1',
                'piso' => 1,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-12',
                'nombre' => 'Aula 12',
                'numero_aula' => 12,
                'tipo' => 'teorica',
                'capacidad' => 40,
                'ubicacion' => 'Edificio FICCT, Piso 1',
                'piso' => 1,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-13',
                'nombre' => 'Aula 13',
                'numero_aula' => 13,
                'tipo' => 'teorica',
                'capacidad' => 40,
                'ubicacion' => 'Edificio FICCT, Piso 1',
                'piso' => 1,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-14',
                'nombre' => 'Aula 14',
                'numero_aula' => 14,
                'tipo' => 'teorica',
                'capacidad' => 40,
                'ubicacion' => 'Edificio FICCT, Piso 1',
                'piso' => 1,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-15',
                'nombre' => 'Aula 15',
                'numero_aula' => 15,
                'tipo' => 'teorica',
                'capacidad' => 40,
                'ubicacion' => 'Edificio FICCT, Piso 1',
                'piso' => 1,
                'activo' => true,
            ],

            // Segundo Piso (Aulas 20-25)
            [
                'codigo' => 'AULA-20',
                'nombre' => 'Aula 20',
                'numero_aula' => 20,
                'tipo' => 'teorica',
                'capacidad' => 35,
                'ubicacion' => 'Edificio FICCT, Piso 2',
                'piso' => 2,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-21',
                'nombre' => 'Aula 21',
                'numero_aula' => 21,
                'tipo' => 'teorica',
                'capacidad' => 35,
                'ubicacion' => 'Edificio FICCT, Piso 2',
                'piso' => 2,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-22',
                'nombre' => 'Aula 22',
                'numero_aula' => 22,
                'tipo' => 'teorica',
                'capacidad' => 35,
                'ubicacion' => 'Edificio FICCT, Piso 2',
                'piso' => 2,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-23',
                'nombre' => 'Aula 23',
                'numero_aula' => 23,
                'tipo' => 'teorica',
                'capacidad' => 35,
                'ubicacion' => 'Edificio FICCT, Piso 2',
                'piso' => 2,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-24',
                'nombre' => 'Aula 24',
                'numero_aula' => 24,
                'tipo' => 'teorica',
                'capacidad' => 35,
                'ubicacion' => 'Edificio FICCT, Piso 2',
                'piso' => 2,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-25',
                'nombre' => 'Aula 25',
                'numero_aula' => 25,
                'tipo' => 'teorica',
                'capacidad' => 35,
                'ubicacion' => 'Edificio FICCT, Piso 2',
                'piso' => 2,
                'activo' => true,
            ],

            // Tercer Piso (Aulas 30-35)
            [
                'codigo' => 'AULA-30',
                'nombre' => 'Aula 30',
                'numero_aula' => 30,
                'tipo' => 'teorica',
                'capacidad' => 30,
                'ubicacion' => 'Edificio FICCT, Piso 3',
                'piso' => 3,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-31',
                'nombre' => 'Aula 31',
                'numero_aula' => 31,
                'tipo' => 'teorica',
                'capacidad' => 30,
                'ubicacion' => 'Edificio FICCT, Piso 3',
                'piso' => 3,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-32',
                'nombre' => 'Aula 32',
                'numero_aula' => 32,
                'tipo' => 'teorica',
                'capacidad' => 30,
                'ubicacion' => 'Edificio FICCT, Piso 3',
                'piso' => 3,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-33',
                'nombre' => 'Aula 33',
                'numero_aula' => 33,
                'tipo' => 'teorica',
                'capacidad' => 30,
                'ubicacion' => 'Edificio FICCT, Piso 3',
                'piso' => 3,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-34',
                'nombre' => 'Aula 34',
                'numero_aula' => 34,
                'tipo' => 'teorica',
                'capacidad' => 30,
                'ubicacion' => 'Edificio FICCT, Piso 3',
                'piso' => 3,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-35',
                'nombre' => 'Aula 35',
                'numero_aula' => 35,
                'tipo' => 'teorica',
                'capacidad' => 30,
                'ubicacion' => 'Edificio FICCT, Piso 3',
                'piso' => 3,
                'activo' => true,
            ],

            // Cuarto Piso (Aulas 40-45)
            [
                'codigo' => 'AULA-40',
                'nombre' => 'Aula 40',
                'numero_aula' => 40,
                'tipo' => 'teorica',
                'capacidad' => 25,
                'ubicacion' => 'Edificio FICCT, Piso 4',
                'piso' => 4,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-41',
                'nombre' => 'Aula 41',
                'numero_aula' => 41,
                'tipo' => 'teorica',
                'capacidad' => 25,
                'ubicacion' => 'Edificio FICCT, Piso 4',
                'piso' => 4,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-42',
                'nombre' => 'Aula 42',
                'numero_aula' => 42,
                'tipo' => 'teorica',
                'capacidad' => 25,
                'ubicacion' => 'Edificio FICCT, Piso 4',
                'piso' => 4,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-43',
                'nombre' => 'Aula 43',
                'numero_aula' => 43,
                'tipo' => 'teorica',
                'capacidad' => 25,
                'ubicacion' => 'Edificio FICCT, Piso 4',
                'piso' => 4,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-44',
                'nombre' => 'Aula 44',
                'numero_aula' => 44,
                'tipo' => 'teorica',
                'capacidad' => 25,
                'ubicacion' => 'Edificio FICCT, Piso 4',
                'piso' => 4,
                'activo' => true,
            ],
            [
                'codigo' => 'AULA-45',
                'nombre' => 'Aula 45',
                'numero_aula' => 45,
                'tipo' => 'teorica',
                'capacidad' => 25,
                'ubicacion' => 'Edificio FICCT, Piso 4',
                'piso' => 4,
                'activo' => true,
            ],
        ];

        foreach ($aulas as $aula) {
            Aulas::updateOrCreate(
                ['codigo' => $aula['codigo']],
                $aula
            );
        }

        $this->command->info('✅ ' . count($aulas) . ' aulas creadas exitosamente.');
    }
}
