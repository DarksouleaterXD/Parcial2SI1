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
            [
                'codigo' => 'A101',
                'nombre' => 'Aula de Clases A-101',
                'tipo' => 'teorica',
                'capacidad' => 40,
                'ubicacion' => 'Edificio A, Piso 1',
                'piso' => 1,
                'activo' => true,
            ],
            [
                'codigo' => 'A102',
                'nombre' => 'Aula de Clases A-102',
                'tipo' => 'teorica',
                'capacidad' => 35,
                'ubicacion' => 'Edificio A, Piso 1',
                'piso' => 1,
                'activo' => true,
            ],
            [
                'codigo' => 'B201',
                'nombre' => 'Aula de Práctica B-201',
                'tipo' => 'practica',
                'capacidad' => 25,
                'ubicacion' => 'Edificio B, Piso 2',
                'piso' => 2,
                'activo' => true,
            ],
            [
                'codigo' => 'LAB301',
                'nombre' => 'Laboratorio de Informática',
                'tipo' => 'laboratorio',
                'capacidad' => 30,
                'ubicacion' => 'Edificio C, Piso 3',
                'piso' => 3,
                'activo' => true,
            ],
            [
                'codigo' => 'LAB302',
                'nombre' => 'Laboratorio de Física',
                'tipo' => 'laboratorio',
                'capacidad' => 20,
                'ubicacion' => 'Edificio C, Piso 3',
                'piso' => 3,
                'activo' => true,
            ],
            [
                'codigo' => 'AUDITORIO',
                'nombre' => 'Auditorio Principal',
                'tipo' => 'mixta',
                'capacidad' => 150,
                'ubicacion' => 'Edificio Principal',
                'piso' => 0,
                'activo' => true,
            ],
        ];

        foreach ($aulas as $aula) {
            Aulas::create($aula);
        }
    }
}
