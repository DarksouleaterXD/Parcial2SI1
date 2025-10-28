<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Persona;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'make:superadmin {email?} {password?}';
    protected $description = 'Crear un superadmin/superusuario en el sistema';

    public function handle()
    {
        $email = $this->argument('email') ?? $this->ask('¿Cuál es el email del superadmin?');
        $password = $this->argument('password') ?? $this->secret('¿Cuál es la contraseña?');
        $nombre = $this->ask('¿Cuál es el nombre?', 'Admin');
        $apellido = $this->ask('¿Cuál es el apellido?', 'Sistema');
        $ci = $this->ask('¿Cuál es el CI/Cédula?', '0000000');

        // Validar que el email no exista
        if (User::where('email', $email)->exists()) {
            $this->error('❌ Este email ya está registrado');
            return 1;
        }

        try {
            // Crear persona
            $persona = Persona::create([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'correo' => $email,
                'ci' => $ci,
            ]);

            // Crear usuario superadmin
            $user = User::create([
                'nombre' => $nombre,
                'email' => $email,
                'password' => Hash::make($password),
                'rol' => 'admin',
                'activo' => true,
                'id_persona' => $persona->id_persona,
            ]);

            $this->info('✅ Superadmin creado exitosamente');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Email', $user->email],
                    ['Nombre', $user->nombre],
                    ['Rol', $user->rol],
                    ['ID', $user->id],
                    ['Activo', $user->activo ? 'Sí' : 'No'],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
