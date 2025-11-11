<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Persona;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsuariosImport implements ToCollection, WithHeadingRow
{
    protected $resultados = [];
    protected $cisEnArchivo = [];
    protected $emailsEnArchivo = [];
    protected $soloValidar = true;
    protected $validarDuplicadosBD = true;

    public function __construct($soloValidar = true, $validarDuplicadosBD = true)
    {
        $this->soloValidar = $soloValidar;
        $this->validarDuplicadosBD = $validarDuplicadosBD;
    }

    /**
     * Procesa la colección de filas del archivo
     */
    public function collection(Collection $rows)
    {
        $this->cisEnArchivo = [];
        $this->emailsEnArchivo = [];
        $filaNumero = 2; // Empieza en 2 porque la fila 1 son headers

        foreach ($rows as $row) {
            $resultado = $this->procesarFila($row, $filaNumero);
            $this->resultados[] = $resultado;
            $filaNumero++;
        }
    }

    /**
     * Procesa y valida una fila individual
     */
    protected function procesarFila($row, $filaNumero)
    {
        $datos = [
            'ci' => $row['ci'] ?? null,
            'nombre' => $row['nombre'] ?? null,
            'apellido_paterno' => $row['apellido_paterno'] ?? null,
            'apellido_materno' => $row['apellido_materno'] ?? null,
            'email' => $row['email'] ?? null,
            'telefono' => $row['telefono'] ?? null,
            'rol' => $row['rol'] ?? null,
            'fecha_nacimiento' => $row['fecha_nacimiento'] ?? null,
            'password' => $row['password'] ?? null,
        ];

        $errores = [];

        // Validar campos obligatorios
        if (empty($datos['ci'])) {
            $errores[] = 'CI es obligatorio';
        } else {
            // Validar formato CI
            if (!is_numeric($datos['ci']) || strlen($datos['ci']) < 7 || strlen($datos['ci']) > 10) {
                $errores[] = 'CI debe ser numérico entre 7 y 10 dígitos';
            }

            // Validar CI único en archivo
            if (in_array($datos['ci'], $this->cisEnArchivo)) {
                $errores[] = 'CI duplicado en el archivo';
            } else {
                $this->cisEnArchivo[] = $datos['ci'];
            }

            // Validar CI único en base de datos (solo si está habilitado)
            if ($this->validarDuplicadosBD && Persona::where('ci', $datos['ci'])->exists()) {
                $errores[] = 'CI ya existe en el sistema';
            }
        }

        if (empty($datos['nombre'])) {
            $errores[] = 'Nombre es obligatorio';
        }

        if (empty($datos['apellido_paterno'])) {
            $errores[] = 'Apellido paterno es obligatorio';
        }

        if (empty($datos['email'])) {
            $errores[] = 'Email es obligatorio';
        } else {
            // Validar formato email
            if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'Email inválido';
            }

            // Validar email único en archivo
            if (in_array($datos['email'], $this->emailsEnArchivo)) {
                $errores[] = 'Email duplicado en el archivo';
            } else {
                $this->emailsEnArchivo[] = $datos['email'];
            }

            // Validar email único en base de datos (solo si está habilitado)
            if ($this->validarDuplicadosBD && User::where('email', $datos['email'])->exists()) {
                $errores[] = 'Email ya existe en el sistema';
            }
        }

        if (empty($datos['rol'])) {
            $errores[] = 'Rol es obligatorio';
        } else {
            // Validar rol permitido
            $rolesPermitidos = ['admin', 'coordinador', 'docente', 'autoridad'];
            if (!in_array($datos['rol'], $rolesPermitidos)) {
                $errores[] = 'Rol debe ser: admin, coordinador, docente o autoridad';
            }
        }

        // Validar teléfono (opcional)
        if (!empty($datos['telefono'])) {
            if (!is_numeric($datos['telefono']) || strlen($datos['telefono']) < 8) {
                $errores[] = 'Teléfono debe ser numérico con al menos 8 dígitos';
            }
        }

        // Validar fecha de nacimiento (opcional)
        if (!empty($datos['fecha_nacimiento'])) {
            $fecha = null;
            try {
                // Intentar parsear la fecha
                if (is_numeric($datos['fecha_nacimiento'])) {
                    // Es un número de Excel (días desde 1900-01-01)
                    $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($datos['fecha_nacimiento']);
                    $datos['fecha_nacimiento'] = $fecha->format('Y-m-d');
                } else {
                    // Intentar parsear como string
                    $fecha = date_create($datos['fecha_nacimiento']);
                    if ($fecha) {
                        $datos['fecha_nacimiento'] = $fecha->format('Y-m-d');
                    } else {
                        throw new \Exception('Formato inválido');
                    }
                }
            } catch (\Exception $e) {
                $errores[] = 'Fecha de nacimiento inválida (use formato YYYY-MM-DD)';
            }
        }

        // Validar password (opcional, se genera automático si no se proporciona)
        if (!empty($datos['password'])) {
            if (strlen($datos['password']) < 6) {
                $errores[] = 'Password debe tener al menos 6 caracteres';
            }
        }

        return [
            'fila' => $filaNumero,
            'datos' => $datos,
            'valido' => empty($errores),
            'errores' => $errores,
        ];
    }

    /**
     * Retorna los resultados de la validación
     */
    public function getResultados()
    {
        return $this->resultados;
    }

    /**
     * Retorna estadísticas de la importación
     */
    public function getEstadisticas()
    {
        $total = count($this->resultados);
        $validos = count(array_filter($this->resultados, fn($r) => $r['valido']));
        $errores = $total - $validos;

        return [
            'total' => $total,
            'validos' => $validos,
            'errores' => $errores,
        ];
    }
}
