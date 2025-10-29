<?php

namespace Database\Seeders;

use App\Models\Docente;
use App\Models\Persona;
use Illuminate\Database\Seeder;

class DocenteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $docentesData = [
            [
                'nombre' => 'Dr. Juan Carlos Rodríguez',
                'cedula' => '0123456789',
                'especialidad' => 'Ingeniería de Software',
                'correo' => 'j.rodriguez@ficct.edu.ec',
                'telefono' => '0991234567',
            ],
            [
                'nombre' => 'Ing. María García López',
                'cedula' => '0987654321',
                'especialidad' => 'Bases de Datos',
                'correo' => 'm.garcia@ficct.edu.ec',
                'telefono' => '0992345678',
            ],
            [
                'nombre' => 'Lic. Pedro Martínez Suárez',
                'cedula' => '0112233445',
                'especialidad' => 'Programación Web',
                'correo' => 'p.martinez@ficct.edu.ec',
                'telefono' => '0993456789',
            ],
            [
                'nombre' => 'Mg. Sofia Fernández Ruiz',
                'cedula' => '0556677889',
                'especialidad' => 'Seguridad Informática',
                'correo' => 's.fernandez@ficct.edu.ec',
                'telefono' => '0994567890',
            ],
            [
                'nombre' => 'Dr. Carlos Mendoza Vega',
                'cedula' => '0778899001',
                'especialidad' => 'Sistemas Operativos',
                'correo' => 'c.mendoza@ficct.edu.ec',
                'telefono' => '0995678901',
            ],
        ];

        foreach ($docentesData as $data) {
            // Crear persona
            $persona = Persona::create([
                'nombre' => $data['nombre'],
                'ci' => $data['cedula'],
                'correo' => $data['correo'],
                'apellido' => 'Docente',
            ]);

            // Crear docente
            $docente = Docente::create([
                'id_persona' => $persona->id,
                'activo' => true,
            ]);

            echo "✅ Docente creado: {$persona->nombre}\n";
        }
    }
}
