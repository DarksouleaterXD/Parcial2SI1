<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Persona;
use App\Models\Rol;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsuariosImport implements ToCollection, WithHeadingRow
{
    protected $resultados = [];
    protected $cisEnArchivo = [];
    protected $emailsEnArchivo = [];
    protected $soloValidar = true;
    protected $validarDuplicadosBD = true;
    protected $generarPasswords = true;
    protected $rolRbacDefecto = null;

    public function __construct($soloValidar = true, $validarDuplicadosBD = true, $generarPasswords = true, $rolRbacDefecto = null)
    {
        $this->soloValidar = $soloValidar;
        $this->validarDuplicadosBD = $validarDuplicadosBD;
        $this->generarPasswords = $generarPasswords;
        $this->rolRbacDefecto = $rolRbacDefecto;
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

            // Si no es solo validación y la fila es válida, crear el usuario
            if (!$this->soloValidar && $resultado['valido']) {
                try {
                    \Log::info('Intentando crear usuario', [
                        'fila' => $filaNumero,
                        'datos' => $resultado['datos']
                    ]);
                    
                    $passwordGenerado = $this->crearUsuario($resultado['datos']);
                    $this->resultados[count($this->resultados) - 1]['creado'] = true;
                    $this->resultados[count($this->resultados) - 1]['password_generado'] = $passwordGenerado;
                    
                    \Log::info('Usuario creado exitosamente', [
                        'fila' => $filaNumero,
                        'email' => $resultado['datos']['email']
                    ]);
                } catch (\Exception $e) {
                    $this->resultados[count($this->resultados) - 1]['valido'] = false;
                    $this->resultados[count($this->resultados) - 1]['errores'][] = 'Error al crear: ' . $e->getMessage();
                    $this->resultados[count($this->resultados) - 1]['creado'] = false;
                    \Log::error('Error al crear usuario:', [
                        'fila' => $filaNumero,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'datos' => $resultado['datos']
                    ]);
                }
            } else {
                \Log::info('Saltando creación de usuario', [
                    'fila' => $filaNumero,
                    'soloValidar' => $this->soloValidar,
                    'valido' => $resultado['valido'],
                    'errores' => $resultado['errores'] ?? []
                ]);
            }

            $filaNumero++;
        }
    }

    /**
     * Crea un usuario y su persona asociada
     */
    protected function crearUsuario($datos): ?string
    {
        DB::beginTransaction();
        try {
            // Usar password del Excel o generar uno
            $password = null;
            if (!empty($datos['password'])) {
                $password = $datos['password'];
            } elseif ($this->generarPasswords) {
                $password = Str::random(12);
            } else {
                $password = 'temporal123';
            }

            // Crear Persona si tiene CI
            $persona = null;
            if (!empty($datos['ci'])) {
                $persona = Persona::create([
                    'ci' => $datos['ci'],
                    'correo' => $datos['email'],
                    'nombre' => $datos['nombre'],
                    'apellido' => $datos['apellido_paterno'] . ($datos['apellido_materno'] ? ' ' . $datos['apellido_materno'] : ''),
                ]);
            }

            // Crear Usuario
            $usuario = User::create([
                'nombre' => $datos['nombre'] . ' ' . $datos['apellido_paterno'] . ($datos['apellido_materno'] ? ' ' . $datos['apellido_materno'] : ''),
                'email' => $datos['email'],
                'password' => Hash::make($password),
                'rol' => $datos['rol'],
                'id_persona' => $persona ? $persona->id : null,
            ]);

            // Actualizar persona con id_usuario
            if ($persona) {
                $persona->update(['id_usuario' => $usuario->id]);
            }

            // Si el rol es docente, crear registro en tabla docentes
            if ($datos['rol'] === 'docente' && $persona) {
                \App\Models\Docente::create([
                    'id_persona' => $persona->id,
                    'activo' => true,
                ]);
            }

            // Asignar rol RBAC
            if ($this->rolRbacDefecto) {
                DB::table('usuario_rol')->insert([
                    'id_usuario' => $usuario->id,
                    'id_rol' => $this->rolRbacDefecto,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Asignar rol RBAC según el rol del sistema
                $rolRbac = null;
                switch ($datos['rol']) {
                    case 'admin':
                        $rolRbac = Rol::where('nombre', 'Administrador')->first();
                        break;
                    case 'coordinador':
                        $rolRbac = Rol::where('nombre', 'Coordinador')->first();
                        break;
                    case 'docente':
                        $rolRbac = Rol::where('nombre', 'Docente')->first();
                        break;
                    case 'autoridad':
                        $rolRbac = Rol::where('nombre', 'Autoridad')->first();
                        break;
                }

                if ($rolRbac) {
                    DB::table('usuario_rol')->insert([
                        'id_usuario' => $usuario->id,
                        'id_rol' => $rolRbac->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return $password; // Retornar el password generado
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
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
