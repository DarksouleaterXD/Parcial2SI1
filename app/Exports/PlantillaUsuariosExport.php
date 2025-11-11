<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PlantillaUsuariosExport implements FromArray, WithHeadings
{
    /**
     * Retorna datos de ejemplo para la plantilla
     */
    public function array(): array
    {
        return [
            [
                '12345678',
                'Juan',
                'Pérez',
                'López',
                'juan.perez@ejemplo.com',
                '70123456',
                'docente',
                '1990-05-15',
                'MiPassword123',
            ],
        ];
    }

    /**
     * Headers de la plantilla
     */
    public function headings(): array
    {
        return [
            'ci',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'email',
            'telefono',
            'rol',
            'fecha_nacimiento',
            'password',
        ];
    }
}
